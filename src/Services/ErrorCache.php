<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ErrorCache
{
    private const CACHE_KEY = 'log_notifier_errors';

    /**
     * Store an error in cache (used when database storage is disabled)
     */
    public static function store(array $error): void
    {
        $cacheDuration = config('log-notifier.cache_duration', 60);
        $expiresAt = now()->addMinutes($cacheDuration);

        // Get existing errors
        $errors = Cache::get(self::CACHE_KEY, []);

        // Add new error with expiration
        $error['expires_at'] = $expiresAt->toIso8601String();
        $errors[] = $error;

        // Keep only non-expired errors
        $errors = array_filter($errors, function ($e) {
            return Carbon::parse($e['expires_at'])->isFuture();
        });

        // Store updated list
        Cache::put(self::CACHE_KEY, $errors, $expiresAt);
    }

    /**
     * Get cached errors that occurred after a specific timestamp
     */
    public static function getRecent(string $since): array
    {
        $sinceTime = Carbon::parse($since);
        $allErrors = Cache::get(self::CACHE_KEY, []);

        // Filter errors by timestamp
        $recentErrors = array_filter($allErrors, function ($error) use ($sinceTime) {
            // Remove expired errors
            if (isset($error['expires_at'])) {
                if (Carbon::parse($error['expires_at'])->isPast()) {
                    return false;
                }
            }

            // Only include errors after the specified time
            if (isset($error['occurred_at'])) {
                return Carbon::parse($error['occurred_at'])->isAfter($sinceTime);
            }

            return false;
        });

        return array_values($recentErrors);
    }

    /**
     * Get all cached errors (non-expired)
     */
    public static function getAll(): array
    {
        $allErrors = Cache::get(self::CACHE_KEY, []);

        // Filter out expired errors
        return array_filter($allErrors, function ($e) {
            return Carbon::parse($e['expires_at'] ?? now()->addDay())->isFuture();
        });
    }

    /**
     * Clear all cached errors
     */
    public static function clear(): int
    {
        $count = count(Cache::get(self::CACHE_KEY, []));
        Cache::forget(self::CACHE_KEY);
        return $count;
    }

    /**
     * Get cache stats for debugging
     */
    public static function stats(): array
    {
        $allErrors = Cache::get(self::CACHE_KEY, []);
        $expired = 0;
        $active = 0;

        foreach ($allErrors as $error) {
            if (isset($error['expires_at'])) {
                if (Carbon::parse($error['expires_at'])->isPast()) {
                    $expired++;
                } else {
                    $active++;
                }
            }
        }

        return [
            'total' => count($allErrors),
            'active' => $active,
            'expired' => $expired,
            'cache_key' => self::CACHE_KEY,
        ];
    }
}
