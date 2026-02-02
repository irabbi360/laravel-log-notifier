<?php

namespace Irabbi360\LaravelLogNotifier;

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
            ->hasRoute('web');
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
    }
}
