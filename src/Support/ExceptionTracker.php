<?php

namespace Irabbi360\LaravelLogNotifier\Support;

use Throwable;
use Illuminate\Support\Facades\Storage;

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
            // Generate unique error ID
            $errorId = time() * 1000 + random_int(0, 999);
            
            // Create error data without storing in database
            $error = [
                'id' => $errorId,
                'level' => 'error',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'occurred_at' => now()->toIso8601String(),
            ];

            // Write complete error data to signal file for SSE stream
            try {
                $disk = Storage::disk('public');
                $signal = [
                    'error_id' => $errorId,
                    'timestamp' => now()->timestamp,
                    'error' => $error, // Include full error data
                ];

                $disk->put('log-notifier-signal.json', json_encode($signal));
                
                error_log('[Log Notifier] Exception tracked without DB - ID: '.$errorId.', message: '.$exception->getMessage());
            } catch (\Throwable $fileEx) {
                // Silent fail - don't break the app if signal file write fails
                \Log::debug('[Log Notifier] Signal file write failed: ' . $fileEx->getMessage());
            }
        } catch (\Exception $e) {
            // Silent fail - don't break the app
        }
    }
}
