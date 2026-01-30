<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Carbon\Carbon;

class ErrorParser
{
    /**
     * Monolog log line pattern.
     */
    protected string $pattern = '/\[(?<datetime>\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?)\]\s+(?<environment>\w+)\.(?<level>\w+):\s+(?<message>.*?)(?=\[\d{4}-\d{2}-\d{2}|\z)/s';

    /**
     * Stack trace pattern.
     */
    protected string $stackTracePattern = '/Stack trace:\s*\n((?:#\d+.*?\n?)+)/s';

    /**
     * File and line pattern from stack trace.
     */
    protected string $fileLinePattern = '/#0\s+(?<file>[^(]+)\((?<line>\d+)\)/';

    /**
     * Parse log content and extract errors.
     */
    public function parse(string $content, array $levels = ['error', 'critical', 'alert', 'emergency']): array
    {
        $errors = [];
        $levels = array_map('strtoupper', $levels);

        // Split content into individual log entries
        $entries = $this->splitIntoEntries($content);

        foreach ($entries as $entry) {
            $parsed = $this->parseEntry($entry, $levels);
            
            if ($parsed) {
                $errors[] = $parsed;
            }
        }

        return $errors;
    }

    /**
     * Split log content into individual entries.
     */
    protected function splitIntoEntries(string $content): array
    {
        // Split by date pattern at the start of each entry
        $entries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}[T ])/', $content, -1, PREG_SPLIT_NO_EMPTY);
        
        return array_filter($entries, fn($entry) => !empty(trim($entry)));
    }

    /**
     * Parse a single log entry.
     */
    protected function parseEntry(string $entry, array $levels): ?array
    {
        // Match the basic log format
        $pattern = '/^\[(?<datetime>\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?)\]\s+(?<environment>\w+)\.(?<level>\w+):\s+(?<message>.+)$/s';
        
        if (!preg_match($pattern, trim($entry), $matches)) {
            return null;
        }

        $level = strtoupper($matches['level']);
        
        // Only process specified levels
        if (!in_array($level, $levels)) {
            return null;
        }

        $message = $matches['message'];
        $trace = null;
        $file = null;
        $line = null;
        $context = [];

        // Extract stack trace
        if (preg_match($this->stackTracePattern, $message, $traceMatch)) {
            $trace = trim($traceMatch[1]);
            $message = trim(preg_replace($this->stackTracePattern, '', $message));
            
            // Extract file and line from first stack frame
            if (preg_match($this->fileLinePattern, $trace, $fileMatch)) {
                $file = $fileMatch['file'];
                $line = (int) $fileMatch['line'];
            }
        }

        // Try to extract file:line from message if not in stack trace
        if (empty($file) && preg_match('/in\s+([^\s:]+):(\d+)/', $message, $fileMatch)) {
            $file = $fileMatch[1];
            $line = (int) $fileMatch[2];
        }

        // Extract JSON context if present
        if (preg_match('/(\{.*\}|\[.*\])\s*$/s', $message, $contextMatch)) {
            $decoded = json_decode($contextMatch[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $context = $decoded;
                $message = trim(str_replace($contextMatch[1], '', $message));
            }
        }

        // Clean up the message
        $message = $this->cleanMessage($message);
        $trace = $trace ? $this->maskSensitiveData($trace) : null;

        return [
            'level' => strtolower($matches['level']),
            'message' => $this->maskSensitiveData($message),
            'trace' => $trace,
            'file' => $file,
            'line' => $line,
            'environment' => $matches['environment'],
            'context' => $context,
            'occurred_at' => $this->parseDateTime($matches['datetime']),
        ];
    }

    /**
     * Parse datetime string to Carbon instance.
     */
    protected function parseDateTime(string $datetime): Carbon
    {
        try {
            return Carbon::parse($datetime);
        } catch (\Exception $e) {
            return Carbon::now();
        }
    }

    /**
     * Clean up the error message.
     */
    protected function cleanMessage(string $message): string
    {
        // Remove excessive whitespace
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Trim and limit length
        return trim(substr($message, 0, 10000));
    }

    /**
     * Mask sensitive data in the content.
     */
    protected function maskSensitiveData(string $content): string
    {
        $patterns = config('log-notifier.mask_patterns', []);
        $replacement = config('log-notifier.mask_replacement', '[REDACTED]');

        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * Parse a single line for quick checking.
     */
    public function parseLine(string $line, array $levels = ['error', 'critical', 'alert', 'emergency']): ?array
    {
        return $this->parseEntry($line, array_map('strtoupper', $levels));
    }
}
