<?php

namespace Irabbi360\LaravelLogNotifier\Support;

use Throwable;

class ExceptionTracker
{
    /**
     * Track an exception in Log Notifier.
     */
    public static function track(Throwable $exception): void
    {
        if (! config('log-notifier.enabled', true)) {
            return;
        }

        try {
            $repository = app(\Irabbi360\LaravelLogNotifier\Services\ErrorRepository::class);
            $repository->store([
                'level' => 'error',
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'context' => [],
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't break the app
        }
    }
}
