<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class LogWatcher
{
    protected string $logPath;

    protected array $levels;

    protected ErrorParser $parser;

    protected ErrorRepository $repository;

    public function __construct(
        ErrorParser $parser,
        ErrorRepository $repository
    ) {
        $this->logPath = config('log-notifier.log_path', storage_path('logs/laravel.log'));
        $this->levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);
        $this->parser = $parser;
        $this->repository = $repository;
    }

    /**
     * Watch the log file(s) for new errors.
     */
    public function watch(): array
    {
        if (! config('log-notifier.enabled', true)) {
            return [];
        }

        $processedErrors = [];
        $logFiles = $this->getLogFiles();

        foreach ($logFiles as $logFile) {
            $errors = $this->watchFile($logFile);
            $processedErrors = array_merge($processedErrors, $errors);
        }

        return $processedErrors;
    }

    /**
     * Get all log files to monitor.
     */
    protected function getLogFiles(): array
    {
        $logPath = config('log-notifier.log_path', storage_path('logs'));
        $scanAll = config('log-notifier.scan_all_logs', true);

        // If scan_all_logs is disabled or path is a file, return single file
        if (! $scanAll || (File::exists($logPath) && ! File::isDirectory($logPath))) {
            return File::exists($logPath) ? [$logPath] : [];
        }

        // If path is a directory, scan for all .log files
        if (File::isDirectory($logPath)) {
            $files = File::glob($logPath . '/*.log');
            return $files ?: [];
        }

        // Fallback to single file
        return File::exists($logPath) ? [$logPath] : [];
    }

    /**
     * Watch a single log file for new errors.
     */
    protected function watchFile(string $logFile): array
    {
        if (! File::exists($logFile)) {
            return [];
        }

        $lastPosition = $this->getLastPosition($logFile);
        $currentSize = File::size($logFile);

        // If file was rotated or truncated, start from beginning
        if ($currentSize < $lastPosition) {
            $lastPosition = 0;
        }

        // No new content
        if ($currentSize <= $lastPosition) {
            return [];
        }

        $newContent = $this->readNewContent($lastPosition, $currentSize, $logFile);
        $this->saveLastPosition($currentSize, $logFile);

        if (empty($newContent)) {
            return [];
        }

        $errors = $this->parser->parse($newContent, $this->levels);
        $processedErrors = [];

        foreach ($errors as $error) {
            $storedError = $this->repository->store($error);

            if ($storedError) {
                // Toast notifications are handled client-side via polling
                $processedErrors[] = $storedError;
            }
        }

        return $processedErrors;
    }

    /**
     * Read new content from the log file.
     */
    protected function readNewContent(int $start, int $end, string $logFile): string
    {
        $handle = fopen($logFile, 'r');

        if (! $handle) {
            return '';
        }

        fseek($handle, $start);
        $content = fread($handle, $end - $start);
        fclose($handle);

        return $content ?: '';
    }

    /**
     * Get the last read position for a log file.
     */
    protected function getLastPosition(string $logFile = null): int
    {
        $logFile = $logFile ?? $this->logPath;
        $key = $this->getCacheKey($logFile);

        return (int) Cache::get($key, 0);
    }

    /**
     * Save the current position for a log file.
     */
    protected function saveLastPosition(int $position, string $logFile = null): void
    {
        $logFile = $logFile ?? $this->logPath;
        $key = $this->getCacheKey($logFile);

        Cache::put($key, $position, now()->addDays(7));
    }

    /**
     * Generate cache key for a log file.
     */
    protected function getCacheKey(string $logFile): string
    {
        return 'log_notifier_position_' . md5($logFile);
    }

    /**
     * Reset the position tracker for all log files.
     */
    public function resetPosition(): void
    {
        $logFiles = $this->getLogFiles();
        foreach ($logFiles as $logFile) {
            Cache::forget($this->getCacheKey($logFile));
        }
    }

    /**
     * Check if we should send notification for this error.
     */
    protected function shouldNotify($error): bool
    {
        // Check rate limiting
        if (config('log-notifier.rate_limit.enabled', true)) {
            $maxNotifications = config('log-notifier.rate_limit.max_notifications', 10);
            $perMinutes = config('log-notifier.rate_limit.per_minutes', 5);

            $key = 'log_notifier_rate_limit';
            $count = Cache::get($key, 0);

            if ($count >= $maxNotifications) {
                return false;
            }

            Cache::put($key, $count + 1, now()->addMinutes($perMinutes));
        }

        // Only notify for new errors, not duplicates that were incremented
        return $error->wasRecentlyCreated ?? false;
    }

    /**
     * Set custom log path.
     */
    public function setLogPath(string $path): self
    {
        $this->logPath = $path;

        return $this;
    }

    /**
     * Set levels to monitor.
     */
    public function setLevels(array $levels): self
    {
        $this->levels = $levels;

        return $this;
    }
}
