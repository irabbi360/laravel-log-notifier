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
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the application
            // Log this only if there's a specific logger for notifier
            if (config('app.debug')) {
                \Illuminate\Support\Facades\Log::debug('Log Notifier error processing:', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Extract error data from log message.
     */
    protected function extractErrorData(string $message, string $level): ?array
    {
        // Try to extract stack trace and file information
        preg_match('/Stack trace:.*?(?=\[|$)/s', $message, $traceMatch);
        preg_match('/^.*?in\s+(.+?):(\d+)/', $message, $fileMatch);

        // Basic parsing - if we can't find file info, skip it
        if (empty($fileMatch)) {
            return null;
        }

        $file = $fileMatch[1] ?? 'Unknown';
        $line = (int) ($fileMatch[2] ?? 0);

        // Extract first line as message
        $lines = explode("\n", $message);
        $errorMessage = trim(array_shift($lines) ?? 'Unknown error');

        // Remove log level prefix if present
        $errorMessage = preg_replace('/^\[.*?\]\s+/', '', $errorMessage);

        // Generate hash for deduplication
        $hash = sha1($errorMessage.$file.$line);

        return [
            'level' => $level,
            'message' => substr($errorMessage, 0, 500), // Limit to 500 chars
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
