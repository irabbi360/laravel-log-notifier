<?php

namespace Irabbi360\LaravelLogNotifier\Support;

use Throwable;

class ExceptionTracker
{
    /**
     * Track an exception and store it for real-time notification.
     *
     * Captures exception details and stores them in a JSON file.
     * The file contains only the current exception (overwrites previous ones).
     * This allows the SSE stream to deliver real-time notifications to the browser.
     *
     * @param  Throwable  $exception  The exception to track
     */
    public static function track(Throwable $exception): void
    {
        if (! config('log-notifier.enabled', true)) {
            return;
        }

        try {
            // Generate unique error ID based on timestamp + random value
            $errorId = time() * 1000 + random_int(0, 999);

            // Prepare error data with all relevant information
            $error = [
                'id' => $errorId,
                'level' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
                'occurred_at' => now()->toIso8601String(),
            ];

            // Store error to JSON file
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $disk->put('log-notifier-current.json', json_encode($error));
        } catch (\Throwable $e) {
            // Silently fail to prevent disrupting the application
        }
    }
}
