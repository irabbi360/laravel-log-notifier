<?php

namespace Irabbi360\LaravelLogNotifier;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Irabbi360\LaravelLogNotifier\Commands\LaravelLogNotifierCommand;

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
            ->name('laravel-log-notifier')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_log_notifier_table')
            ->hasCommand(LaravelLogNotifierCommand::class);
    }
}
