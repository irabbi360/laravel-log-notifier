<?php

namespace Irabbi360\LaravelLogNotifier\Listeners;

use Illuminate\Log\Events\LogWritten;
use Irabbi360\LaravelLogNotifier\Services\ErrorParser;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;

class ProcessNewLogEntry
{
    protected ErrorParser $parser;

    protected ErrorRepository $repository;

    public function __construct(ErrorParser $parser, ErrorRepository $repository)
    {
        $this->parser = $parser;
        $this->repository = $repository;
    }

    /**
     * Handle the event - process logs in real-time when they're written.
     */
    public function handle(LogWritten $event): void
    {
        // Only process if log notifier is enabled
        if (! config('log-notifier.enabled', true)) {
            return;
        }

        // Get configured levels to monitor
        $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);

        // Debug: Log that listener was triggered
        if (config('log-notifier.debug', false)) {
            \Illuminate\Support\Facades\Log::info('[Log Notifier] Listener triggered', [
                'event_level' => $event->level,
                'is_monitored' => in_array($event->level, $levels),
            ]);
        }

        // Check if this log message is at a level we're monitoring
        if (! in_array($event->level, $levels)) {
            return;
        }

        try {
            // Parse the log message
            $formatted = $event->formatMessage();

            // Extract error details from the formatted message
            $errorData = $this->extractErrorData($formatted, $event->level);

            if ($errorData) {
                // Store the error in database
                $this->repository->store($errorData);

                // Debug logging
                if (config('log-notifier.debug', false)) {
                    \Illuminate\Support\Facades\Log::info('[Log Notifier] Error captured', [
                        'level' => $event->level,
                        'message' => $errorData['message'] ?? 'unknown',
                        'file' => $errorData['file'] ?? 'unknown',
                        'line' => $errorData['line'] ?? 0,
                    ]);
                }
            } else {
                if (config('log-notifier.debug', false)) {
                    \Illuminate\Support\Facades\Log::warning('[Log Notifier] Failed to extract error data', [
                        'level' => $event->level,
                        'message_preview' => substr($formatted, 0, 100),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log errors in debug mode
            if (config('app.debug')) {
                \Illuminate\Support\Facades\Log::error('Log Notifier exception', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Extract error data from log message.
     * More flexible parsing - works with any log format.
     */
    protected function extractErrorData(string $message, string $level): ?array
    {
        // Don't skip empty messages - capture them too
        if (empty(trim($message))) {
            return null;
        }

        // Extract stack trace if available
        preg_match('/Stack trace:.*?(?=\[|$)/s', $message, $traceMatch);

        // Try multiple patterns to extract file and line number
        $file = 'Unknown';
        $line = 0;

        // Pattern 1: "in /path/to/file.php:123"
        if (preg_match('/in\s+(.+?):(\d+)/i', $message, $fileMatch)) {
            $file = trim($fileMatch[1]);
            $line = (int) $fileMatch[2];
        }
        // Pattern 2: "/path/to/file.php:123"
        elseif (preg_match('/(\/[^\s:]+):(\d+)/', $message, $fileMatch)) {
            $file = trim($fileMatch[1]);
            $line = (int) $fileMatch[2];
        }

        // Extract first line as message
        $lines = explode("\n", $message);
        $errorMessage = trim(array_shift($lines) ?? 'Unknown error');

        // Remove log level prefix if present
        $errorMessage = preg_replace('/^\[.*?\]\s+/', '', $errorMessage);
        $errorMessage = preg_replace('/^(emergency|alert|critical|error|warning|notice|info|debug):\s+/i', '', $errorMessage);

        // Limit message length
        if (strlen($errorMessage) > 500) {
            $errorMessage = substr($errorMessage, 0, 500) . '...';
        }

        // Generate hash for deduplication
        $hash = sha1($errorMessage . $file . $line);

        return [
            'level' => $level,
            'message' => $errorMessage,
            'trace' => $traceMatch[0] ?? null,
            'file' => $file,
            'line' => $line,
            'hash' => $hash,
            'environment' => config('app.env'),
            'context' => [],
            'request_data' => $this->getRequestData(),
        ];
    }

    /**
     * Get request context data if available.
     */
    protected function getRequestData(): array
    {
        try {
            if (! request()) {
                return [];
            }

            return [
                'method' => request()->getMethod(),
                'path' => request()->getPathInfo(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
