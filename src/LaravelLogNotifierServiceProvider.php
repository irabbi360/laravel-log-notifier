<?php

namespace Irabbi360\LaravelLogNotifier;

use Illuminate\Log\Events\LogWritten;
use Irabbi360\LaravelLogNotifier\Commands\ClearErrorsCommand;
use Irabbi360\LaravelLogNotifier\Commands\WatchLogsCommand;
use Irabbi360\LaravelLogNotifier\Listeners\ProcessNewLogEntry;
use Irabbi360\LaravelLogNotifier\Services\ErrorParser;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;
use Irabbi360\LaravelLogNotifier\Services\LogWatcher;
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
            ->hasRoute('web')
            ->hasMigrations([
                'create_log_notifier_errors_table',
            ])
            ->hasCommands([
                WatchLogsCommand::class,
                ClearErrorsCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
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

        $this->app->singleton(LogWatcher::class, function ($app) {
            return new LogWatcher(
                $app->make(ErrorParser::class),
                $app->make(ErrorRepository::class)
            );
        });

        // Register the main facade
        $this->app->singleton('log-notifier', function ($app) {
            return new LaravelLogNotifier(
                $app->make(LogWatcher::class),
                $app->make(ErrorRepository::class)
            );
        });
    }

    public function packageBooted(): void
    {
        // Register event listener for real-time log processing
        if (config('log-notifier.enabled', true) && config('log-notifier.use_event_listener', true)) {
            $this->app['events']->listen(
                LogWritten::class,
                ProcessNewLogEntry::class
            );
        }
    }
}
