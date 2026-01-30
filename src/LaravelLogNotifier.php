<?php

namespace Irabbi360\LaravelLogNotifier;

use Irabbi360\LaravelLogNotifier\Models\LogError;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;
use Irabbi360\LaravelLogNotifier\Services\LogWatcher;

class LaravelLogNotifier
{
    protected LogWatcher $watcher;

    protected ErrorRepository $repository;

    public function __construct(
        LogWatcher $watcher,
        ErrorRepository $repository
    ) {
        $this->watcher = $watcher;
        $this->repository = $repository;
    }

    /**
     * Watch for new errors in the log file.
     */
    public function watch(): array
    {
        return $this->watcher->watch();
    }

    /**
     * Get all errors with optional filtering.
     */
    public function getErrors(array $filters = [], int $perPage = 20)
    {
        return $this->repository->getErrors($filters, $perPage);
    }

    /**
     * Get a single error by ID.
     */
    public function getError(int $id): ?LogError
    {
        return $this->repository->find($id);
    }

    /**
     * Get error statistics.
     */
    public function getStatistics(int $days = 7): array
    {
        return $this->repository->getStatistics($days);
    }

    /**
     * Mark an error as resolved.
     */
    public function resolve(int $id, ?int $userId = null, ?string $note = null): bool
    {
        return $this->repository->resolve($id, $userId, $note);
    }

    /**
     * Mark an error as unresolved.
     */
    public function unresolve(int $id): bool
    {
        return $this->repository->unresolve($id);
    }

    /**
     * Delete an error.
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Clear old errors based on retention policy.
     */
    public function clearOldErrors(): int
    {
        return $this->repository->clearOldErrors();
    }

    /**
     * Clear all errors.
     */
    public function clearAll(): int
    {
        return $this->repository->clearAll();
    }

    /**
     * Reset the log position tracker.
     */
    public function resetPosition(): void
    {
        $this->watcher->resetPosition();
    }

    /**
     * Get the log watcher instance.
     */
    public function watcher(): LogWatcher
    {
        return $this->watcher;
    }

    /**
     * Get the error repository instance.
     */
    public function repository(): ErrorRepository
    {
        return $this->repository;
    }
}
