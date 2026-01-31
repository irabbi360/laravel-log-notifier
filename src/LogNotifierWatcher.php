<?php

namespace Irabbi360\LaravelLogNotifier;

use Throwable;
use Laravel\Framework\Foundation\Configuration\Exceptions;
use Irabbi360\LaravelLogNotifier\Support\ExceptionTracker;

class LogNotifierWatcher
{
    /**
     * Register exception handling with Log Notifier
     *
     * Usage in bootstrap/app.php:
     * LogNotifierWatcher::handles($exceptions);
     *
     * @param Exceptions $exceptions
     * @return void
     */
    public static function handles(Exceptions $exceptions): void
    {
        $exceptions->shouldReport(function (Throwable $e) {
            if (config('log-notifier.enabled', true)) {
                ExceptionTracker::track($e);
            }
            return true;
        });
    }
}
