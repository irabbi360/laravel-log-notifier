<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Irabbi360\LaravelLogNotifier\Models\LogError;
use Carbon\Carbon;

class ErrorRepository
{
    /**
     * Store an error in the database.
     */
    public function store(array $error): ?LogError
    {
        $hash = $this->generateHash($error);

        // Check for deduplication
        if (config('log-notifier.deduplicate', true)) {
            $existing = $this->findDuplicate($hash);
            
            if ($existing) {
                $existing->incrementOccurrence();
                return $existing;
            }
        }

        $logError = LogError::create([
            'level' => $error['level'],
            'message' => $error['message'],
            'trace' => $error['trace'],
            'file' => $error['file'],
            'line' => $error['line'],
            'hash' => $hash,
            'environment' => $error['environment'] ?? config('app.env'),
            'context' => $error['context'] ?? null,
            'request_data' => $this->captureRequestData(),
            'occurrence_count' => 1,
            'first_occurred_at' => $error['occurred_at'] ?? now(),
            'last_occurred_at' => $error['occurred_at'] ?? now(),
        ]);

        $logError->wasRecentlyCreated = true;

        return $logError;
    }

    /**
     * Generate a hash for deduplication.
     */
    protected function generateHash(array $error): string
    {
        $hashData = implode('|', [
            $error['message'] ?? '',
            $error['file'] ?? '',
            $error['line'] ?? '',
        ]);

        return hash('sha256', $hashData);
    }

    /**
     * Find a duplicate error within the deduplication window.
     */
    protected function findDuplicate(string $hash): ?LogError
    {
        $window = config('log-notifier.deduplicate_window', 60);

        return LogError::where('hash', $hash)
            ->where('is_resolved', false)
            ->where('last_occurred_at', '>=', now()->subMinutes($window))
            ->first();
    }

    /**
     * Capture current request data.
     */
    protected function captureRequestData(): ?array
    {
        if (!app()->runningInConsole() && request()) {
            return [
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ];
        }

        return null;
    }

    /**
     * Get all errors with optional filters.
     */
    public function getErrors(array $filters = [], int $perPage = 20)
    {
        $query = LogError::query();

        if (!empty($filters['level'])) {
            $query->level($filters['level']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['is_resolved'])) {
            $filters['is_resolved'] ? $query->resolved() : $query->unresolved();
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->between($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('last_occurred_at', 'desc')->paginate($perPage);
    }

    /**
     * Get error by ID.
     */
    public function find(int $id): ?LogError
    {
        return LogError::find($id);
    }

    /**
     * Get error statistics.
     */
    public function getStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'total' => LogError::where('last_occurred_at', '>=', $startDate)->count(),
            'unresolved' => LogError::unresolved()->where('last_occurred_at', '>=', $startDate)->count(),
            'resolved' => LogError::resolved()->where('last_occurred_at', '>=', $startDate)->count(),
            'by_level' => LogError::where('last_occurred_at', '>=', $startDate)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'by_day' => LogError::where('last_occurred_at', '>=', $startDate)
                ->selectRaw('DATE(last_occurred_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * Mark an error as resolved.
     */
    public function resolve(int $id, ?int $userId = null, ?string $note = null): bool
    {
        $error = $this->find($id);
        
        if (!$error) {
            return false;
        }

        return $error->markAsResolved($userId, $note);
    }

    /**
     * Mark an error as unresolved.
     */
    public function unresolve(int $id): bool
    {
        $error = $this->find($id);
        
        if (!$error) {
            return false;
        }

        return $error->markAsUnresolved();
    }

    /**
     * Delete an error.
     */
    public function delete(int $id): bool
    {
        return LogError::destroy($id) > 0;
    }

    /**
     * Clear old errors based on retention policy.
     */
    public function clearOldErrors(): int
    {
        $retentionDays = config('log-notifier.retention_days', 30);
        
        if ($retentionDays <= 0) {
            return 0;
        }

        return LogError::where('last_occurred_at', '<', now()->subDays($retentionDays))->delete();
    }

    /**
     * Clear all errors.
     */
    public function clearAll(): int
    {
        return LogError::truncate();
    }

    /**
     * Bulk resolve errors.
     */
    public function bulkResolve(array $ids, ?int $userId = null): int
    {
        return LogError::whereIn('id', $ids)->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    /**
     * Bulk delete errors.
     */
    public function bulkDelete(array $ids): int
    {
        return LogError::destroy($ids);
    }
}
