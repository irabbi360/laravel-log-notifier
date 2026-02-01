<?php

namespace Irabbi360\LaravelLogNotifier\Services;

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
            $files = File::glob($logPath.'/*.log');

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
     * Check if we should send notification for this error.
     */
    protected function shouldNotify($error): bool
    {
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
