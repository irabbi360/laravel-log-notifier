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

    protected PushNotifier $notifier;

    public function __construct(
        ErrorParser $parser,
        ErrorRepository $repository,
        PushNotifier $notifier
    ) {
        $this->logPath = config('log-notifier.log_path', storage_path('logs/laravel.log'));
        $this->levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);
        $this->parser = $parser;
        $this->repository = $repository;
        $this->notifier = $notifier;
    }

    /**
     * Watch the log file for new errors.
     */
    public function watch(): array
    {
        if (! config('log-notifier.enabled', true)) {
            return [];
        }

        if (! File::exists($this->logPath)) {
            return [];
        }

        $lastPosition = $this->getLastPosition();
        $currentSize = File::size($this->logPath);

        // If file was rotated or truncated, start from beginning
        if ($currentSize < $lastPosition) {
            $lastPosition = 0;
        }

        // No new content
        if ($currentSize <= $lastPosition) {
            return [];
        }

        $newContent = $this->readNewContent($lastPosition, $currentSize);
        $this->saveLastPosition($currentSize);

        if (empty($newContent)) {
            return [];
        }

        $errors = $this->parser->parse($newContent, $this->levels);
        $processedErrors = [];

        foreach ($errors as $error) {
            $storedError = $this->repository->store($error);

            if ($storedError && $this->shouldNotify($storedError)) {
                $this->notifier->notify($storedError);
                $processedErrors[] = $storedError;
            }
        }

        return $processedErrors;
    }

    /**
     * Read new content from the log file.
     */
    protected function readNewContent(int $start, int $end): string
    {
        $handle = fopen($this->logPath, 'r');

        if (! $handle) {
            return '';
        }

        fseek($handle, $start);
        $content = fread($handle, $end - $start);
        fclose($handle);

        return $content ?: '';
    }

    /**
     * Get the last read position from cache.
     */
    protected function getLastPosition(): int
    {
        return Cache::get($this->getCacheKey(), 0);
    }

    /**
     * Save the current position to cache.
     */
    protected function saveLastPosition(int $position): void
    {
        Cache::forever($this->getCacheKey(), $position);
    }

    /**
     * Get the cache key for storing position.
     */
    protected function getCacheKey(): string
    {
        return 'log_notifier_position_'.md5($this->logPath);
    }

    /**
     * Reset the position tracker.
     */
    public function resetPosition(): void
    {
        Cache::forget($this->getCacheKey());
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
