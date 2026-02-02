<?php

namespace Irabbi360\LaravelLogNotifier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    /**
     * Server-Sent Events stream for real-time error notifications.
     *
     * Clients connect via EventSource and receive error alerts in real-time.
     * The stream:
     * - Sends pending errors on reconnection
     * - Monitors the current error file for new exceptions
     * - Maintains connection for 25 seconds with 10-second heartbeats
     * - Automatically closes and allows client to reconnect
     */
    public function stream(Request $request)
    {
        // Set proper headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Prevent output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            // Get Last-Event-ID from request header
            $lastEventId = (int) ($request->header('Last-Event-ID') ?? 0);

            // Send initial connection message
            echo ": Connected\n\n";
            flush();

            // On initial connection (lastEventId=0), don't send old errors
            // Only send NEW errors that occur AFTER this moment
            if ($lastEventId > 0) {
                try {
                    $pendingErrors = $this->getPendingErrors($lastEventId);

                    if (! empty($pendingErrors)) {
                        foreach ($pendingErrors as $error) {
                            echo "id: {$error['id']}\n";
                            echo 'data: '.json_encode($error)."\n\n";
                            flush();
                        }
                    }
                } catch (\Exception $ex) {
                    // Continue if error retrieval fails
                }
            }

            // Keep connection alive for 25 seconds
            // Read current error from file - only send if it's NEWER than connection start
            $startTime = time();
            $maxDuration = 25; // seconds
            $lastHeartbeat = $startTime;

            // Initialize lastErrorId with current error ID from file (so old errors don't show on load)
            $lastErrorId = 0;
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk('public');
                $errorFileName = 'log-notifier-current.json';

                if ($disk->exists($errorFileName)) {
                    $errorContent = $disk->get($errorFileName);
                    if ($errorContent) {
                        $error = @json_decode($errorContent, true);
                        if (is_array($error) && isset($error['id'])) {
                            $lastErrorId = (int) $error['id'];
                        }
                    }
                }
            } catch (\Exception $ex) {
                // Continue with lastErrorId = 0
            }

            while ((time() - $startTime) < $maxDuration) {
                if (connection_aborted()) {
                    break;
                }

                $currentTime = time();

                // Check for current error
                try {
                    $disk = \Illuminate\Support\Facades\Storage::disk('public');
                    $errorFileName = 'log-notifier-current.json';

                    if ($disk->exists($errorFileName)) {
                        $errorContent = $disk->get($errorFileName);

                        if ($errorContent) {
                            $error = @json_decode($errorContent, true);

                            if (is_array($error) && isset($error['id'])) {
                                $errorId = (int) $error['id'];

                                // Only send if error ID is greater than last sent (new error)
                                if ($errorId > $lastErrorId) {
                                    $lastErrorId = $errorId;

                                    echo "id: {$errorId}\n";
                                    echo 'data: '.json_encode([
                                        'id' => $errorId,
                                        'level' => $error['level'] ?? 'error',
                                        'message' => $error['message'] ?? 'Unknown error',
                                        'trace' => $error['trace'] ?? '',
                                        'file' => $error['file'] ?? 'unknown',
                                        'line' => $error['line'] ?? 0,
                                        'occurred_at' => $error['occurred_at'] ?? now()->toIso8601String(),
                                    ])."\n\n";
                                    flush();
                                }
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    // Continue monitoring even if there's a read error
                }

                // Send heartbeat every 10 seconds
                if (($currentTime - $lastHeartbeat) >= 10) {
                    echo ": heartbeat\n\n";
                    flush();
                    $lastHeartbeat = $currentTime;
                }

                usleep(100000); // 0.1 seconds
            }

            // Close connection gracefully
            echo "event: close\n";
            echo "data: Connection timeout\n\n";
            flush();
        } catch (\Exception $e) {
            // Only send error event if headers not sent
            if (! headers_sent()) {
                echo "event: error\n";
                echo 'data: Stream error'."\n\n";
                flush();
            }
        }

        exit;
    }

    /**
     * Get pending errors since last event ID
     * Only retrieves errors that haven't been sent yet and are recent
     */
    protected function getPendingErrors($lastEventId = 0, $secondsRecent = null): array
    {
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $errorFileName = 'log-notifier-current.json';

            if (! $disk->exists($errorFileName)) {
                return [];
            }

            $errorContent = $disk->get($errorFileName);

            if (! $errorContent) {
                return [];
            }

            $error = @json_decode($errorContent, true);

            if (! is_array($error) || ! isset($error['id'])) {
                return [];
            }

            $errorId = (int) $error['id'];

            // Only return if it's newer than lastEventId
            if ($errorId <= $lastEventId) {
                return [];
            }

            return [[
                'id' => $errorId,
                'level' => $error['level'] ?? 'error',
                'message' => \Illuminate\Support\Str::limit($error['message'] ?? 'Unknown error', 200),
                'trace' => \Illuminate\Support\Str::limit($error['trace'] ?? '', 200),
                'file' => $error['file'] ?? 'unknown',
                'line' => $error['line'] ?? 0,
                'occurred_at' => $error['occurred_at'] ?? now()->toIso8601String(),
            ]];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
