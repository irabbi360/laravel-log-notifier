<?php

namespace Irabbi360\LaravelLogNotifier;

use Irabbi360\LaravelLogNotifier\Support\ExceptionTracker;
use Laravel\Framework\Foundation\Configuration\Exceptions;
use Throwable;

class LogNotifierWatcher
{
    /**
     * Register exception handling with Log Notifier
     *
     * Usage in bootstrap/app.php:
     * LogNotifierWatcher::handles($exceptions);
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
