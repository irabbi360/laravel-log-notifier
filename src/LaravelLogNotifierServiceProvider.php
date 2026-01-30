<?php

namespace Irabbi360\LaravelLogNotifier;

use Irabbi360\LaravelLogNotifier\Commands\ClearErrorsCommand;
use Irabbi360\LaravelLogNotifier\Commands\GenerateVapidKeysCommand;
use Irabbi360\LaravelLogNotifier\Commands\TestNotificationCommand;
use Irabbi360\LaravelLogNotifier\Commands\WatchLogsCommand;
use Irabbi360\LaravelLogNotifier\Services\ErrorParser;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;
use Irabbi360\LaravelLogNotifier\Services\LogWatcher;
use Irabbi360\LaravelLogNotifier\Services\PushNotifier;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelLogNotifierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('log-notifier')
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigrations([
                'create_log_notifier_errors_table',
                'create_log_notifier_subscriptions_table',
            ])
            ->hasCommands([
                WatchLogsCommand::class,
                ClearErrorsCommand::class,
                TestNotificationCommand::class,
                GenerateVapidKeysCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->publishAssets()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('irabbi360/laravel-log-notifier');
            });
    }

    public function packageRegistered(): void
    {
        // Register services as singletons
        $this->app->singleton(ErrorParser::class, function ($app) {
            return new ErrorParser;
        });

        $this->app->singleton(ErrorRepository::class, function ($app) {
            return new ErrorRepository;
        });

        $this->app->singleton(PushNotifier::class, function ($app) {
            return new PushNotifier;
        });

        $this->app->singleton(LogWatcher::class, function ($app) {
            return new LogWatcher(
                $app->make(ErrorParser::class),
                $app->make(ErrorRepository::class),
                $app->make(PushNotifier::class)
            );
        });

        // Register the main facade
        $this->app->singleton('log-notifier', function ($app) {
            return new LaravelLogNotifier(
                $app->make(LogWatcher::class),
                $app->make(ErrorRepository::class),
                $app->make(PushNotifier::class)
            );
        });
    }

    public function packageBooted(): void
    {
        // Publish service worker to public directory
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/assets/log-notifier-sw.js' => public_path('log-notifier-sw.js'),
            ], 'log-notifier-assets');
        }
    }
}
