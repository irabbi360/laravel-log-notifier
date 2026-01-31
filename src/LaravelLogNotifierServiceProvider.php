<?php

namespace Irabbi360\LaravelLogNotifier;

use Illuminate\Log\Events\LogWritten;
use Irabbi360\LaravelLogNotifier\Commands\ClearErrorsCommand;
use Irabbi360\LaravelLogNotifier\Commands\TestCommand;
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
                TestCommand::class,
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

        // Register LogNotifierWatcher facade
        $this->app->singleton('log-notifier-watcher', function ($app) {
            return new \Irabbi360\LaravelLogNotifier\LogNotifierWatcher;
        });
    }

    public function packageBooted(): void
    {
        // Register facade alias for easy access
        if (! class_exists('LogNotifier')) {
            class_alias(
                \Irabbi360\LaravelLogNotifier\Facades\LaravelLogNotifier::class,
                'LogNotifier'
            );
        }

        if (! config('log-notifier.enabled', true)) {
            return;
        }

        // Listen to LogWritten event (fires when any log is written)
        try {
            $this->app['events']->listen(
                LogWritten::class,
                [$this, 'handleLogWritten']
            );
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    /**
     * Handle LogWritten event.
     */
    public function handleLogWritten(LogWritten $event): void
    {
        if (! config('log-notifier.enabled', true)) {
            return;
        }

        try {
            $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);

            if (! in_array($event->level, $levels)) {
                return;
            }

            $listener = $this->app->make(ProcessNewLogEntry::class);
            $listener->handle($event);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
