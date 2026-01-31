<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class LogFileReader
{
    /**
     * Read errors from log files based on configuration
     */
    public static function getErrors(array $filters = [], int $perPage = 20): array
    {
        $logPath = config('log-notifier.log_path', storage_path('logs'));
        $logFiles = self::getLogFiles($logPath);

        $errors = [];

        foreach ($logFiles as $file) {
            $fileErrors = self::parseLogFile($file, $filters);
            $errors = array_merge($errors, $fileErrors);
        }

        // Sort by timestamp descending
        usort($errors, function ($a, $b) {
            $timeA = Carbon::parse($a['occurred_at'] ?? now())->timestamp;
            $timeB = Carbon::parse($b['occurred_at'] ?? now())->timestamp;

            return $timeB <=> $timeA;
        });

        // Paginate
        $offset = 0; // Could be improved with request()->get('page')

        return array_slice($errors, $offset, $perPage);
    }

    /**
     * Get recent errors since a specific timestamp
     */
    public static function getRecent(?string $since = null): array
    {
        $logPath = config('log-notifier.log_path', storage_path('logs'));
        $logFiles = self::getLogFiles($logPath);

        $errors = [];
        $sinceTime = $since ? Carbon::parse($since) : now()->subMinutes(10);

        foreach ($logFiles as $file) {
            $fileErrors = self::parseLogFile($file, [], $sinceTime);
            $errors = array_merge($errors, $fileErrors);
        }

        // Sort by timestamp descending
        usort($errors, function ($a, $b) {
            $timeA = Carbon::parse($a['occurred_at'] ?? now())->timestamp;
            $timeB = Carbon::parse($b['occurred_at'] ?? now())->timestamp;

            return $timeB <=> $timeA;
        });

        return array_slice($errors, 0, 10);
    }

    /**
     * Get all log files to read
     */
    protected static function getLogFiles(string $path): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (! is_dir($path)) {
            return [];
        }

        $scanAll = config('log-notifier.scan_all_logs', true);

        if ($scanAll) {
            return glob($path.'/*.log') ?: [];
        }

        return [];
    }

    /**
     * Parse a single log file for errors
     */
    protected static function parseLogFile(string $filePath, array $filters = [], ?Carbon $since = null): array
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $errors = [];
        $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);
        $content = file_get_contents($filePath);

        // Split by stack traces
        $lines = explode("\n", $content);
        $currentError = null;
        $errorId = 0;

        foreach ($lines as $line) {
            // Check for error level indicators
            $level = self::extractLevel($line);

            if ($level && in_array($level, $levels)) {
                // Save previous error if exists
                if ($currentError) {
                    $errors[] = $currentError;
                    $errorId++;
                }

                // Start new error
                $currentError = [
                    'id' => uniqid('file_'),
                    'level' => $level,
                    'message' => self::extractMessage($line),
                    'file' => self::extractFile($line),
                    'line' => self::extractLine($line),
                    'trace' => '',
                    'occurred_at' => self::extractTimestamp($line) ?? now()->toIso8601String(),
                    'source' => 'file',
                ];

                // Apply filters
                if (! self::matchesFilters($currentError, $filters, $since)) {
                    $currentError = null;
                }
            } elseif ($currentError && trim($line)) {
                // Append to trace
                $currentError['trace'] .= $line."\n";
            }
        }

        // Add last error
        if ($currentError) {
            $errors[] = $currentError;
        }

        return $errors;
    }

    /**
     * Extract log level from log line
     */
    protected static function extractLevel(string $line): ?string
    {
        $patterns = [
            'emergency' => '/\[emergency\]|\bEMERGENCY\b/i',
            'alert' => '/\[alert\]|\bALERT\b/i',
            'critical' => '/\[critical\]|\bCRITICAL\b/i',
            'error' => '/\[error\]|\bERROR\b/i',
            'warning' => '/\[warning\]|\bWARNING\b/i',
            'notice' => '/\[notice\]|\bNOTICE\b/i',
            'info' => '/\[info\]|\bINFO\b/i',
            'debug' => '/\[debug\]|\bDEBUG\b/i',
        ];

        foreach ($patterns as $level => $pattern) {
            if (preg_match($pattern, $line)) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Extract error message from log line
     */
    protected static function extractMessage(string $line): string
    {
        // Remove timestamp and level
        $line = preg_replace('/^\[.*?\]\s*/i', '', $line);
        $line = preg_replace('/\[(emergency|alert|critical|error|warning|notice|info|debug)\]\s*/i', '', $line);

        // Keep first 200 chars
        return trim(substr($line, 0, 200));
    }

    /**
     * Extract file name from log line
     */
    protected static function extractFile(string $line): string
    {
        // Look for file path patterns
        if (preg_match('/(\S+\.php):(\d+)/', $line, $matches)) {
            return $matches[1];
        }

        // Try to find in trace
        if (preg_match('/in\s+(\S+\.php)/', $line, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }

    /**
     * Extract line number from log line
     */
    protected static function extractLine(string $line): int
    {
        if (preg_match('/(\S+\.php):(\d+)/', $line, $matches)) {
            return (int) $matches[2];
        }

        return 0;
    }

    /**
     * Extract timestamp from log line
     */
    protected static function extractTimestamp(string $line): ?string
    {
        // Laravel log format: [2026-01-31 12:00:00]
        if (preg_match('/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            try {
                return Carbon::parse($matches[1])->toIso8601String();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Check if error matches filters
     */
    protected static function matchesFilters(array $error, array $filters, ?Carbon $since): bool
    {
        // Check level filter
        if (! empty($filters['level']) && $error['level'] !== $filters['level']) {
            return false;
        }

        // Check search filter
        if (! empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $message = strtolower($error['message']);

            if (strpos($message, $search) === false) {
                return false;
            }
        }

        // Check timestamp filter
        if ($since) {
            try {
                $errorTime = Carbon::parse($error['occurred_at']);
                if ($errorTime->isBefore($since)) {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get statistics from log files
     */
    public static function getStatistics(int $days = 7): array
    {
        $logPath = config('log-notifier.log_path', storage_path('logs'));
        $logFiles = self::getLogFiles($logPath);
        $levels = config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']);

        $stats = [
            'total_errors' => 0,
            'by_level' => array_fill_keys($levels, 0),
            'today' => 0,
            'this_week' => 0,
            'by_day' => [],
        ];

        $now = now();
        $daysAgo = $now->copy()->subDays($days);

        foreach ($logFiles as $file) {
            $errors = self::parseLogFile($file, [], $daysAgo);

            foreach ($errors as $error) {
                $stats['total_errors']++;

                // By level
                if (isset($stats['by_level'][$error['level']])) {
                    $stats['by_level'][$error['level']]++;
                }

                // Today
                if (Carbon::parse($error['occurred_at'])->isToday()) {
                    $stats['today']++;
                }

                // This week
                if (Carbon::parse($error['occurred_at'])->isAfter($now->copy()->subDays(7))) {
                    $stats['this_week']++;
                }
            }
        }

        return $stats;
    }
}
