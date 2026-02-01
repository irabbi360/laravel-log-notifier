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
            // Generate unique error ID based on timestamp
            $errorId = time() * 1000 + random_int(0, 999);

            // Create error data
            $error = [
                'id' => $errorId,
                'level' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'occurred_at' => now()->toIso8601String(),
            ];

            // Write error to file (overwrite previous - only keep current error)
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk('public');

                // Write single error (overwrites previous)
                $disk->put('log-notifier-current.json', json_encode($error));

                error_log('[Log Notifier] Exception logged - ID: '.$errorId.', message: '.$error['message']);
            } catch (\Throwable $fileEx) {
                \Log::debug('[Log Notifier] File write failed: '.$fileEx->getMessage());
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}
