<?php

namespace Irabbi360\LaravelLogNotifier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;
use Irabbi360\LaravelLogNotifier\Services\LogFileReader;

class DashboardController extends Controller
{
    protected ErrorRepository $repository;

    public function __construct(ErrorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display the error dashboard.
     * Reads from database, cache, or files based on configuration.
     */
    public function index(Request $request)
    {
        $filters = [
            'level' => $request->get('level'),
            'search' => $request->get('search'),
            'is_resolved' => $request->has('resolved') ? (bool) $request->get('resolved') : null,
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        // Read from database
        $errors = $this->repository->getErrors(array_filter($filters), 20);
        $statistics = $this->repository->getStatistics();

        return view('log-notifier::dashboard.index', [
            'errors' => $errors,
            'statistics' => $statistics,
            'filters' => $filters,
            'levels' => config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']),
            'source' => 'database',
        ]);
    }

    /**
     * Display a single error.
     */
    public function show(int $id)
    {
        $error = $this->repository->find($id);

        if (! $error) {
            abort(404, 'Error not found');
        }

        return view('log-notifier::dashboard.show', [
            'error' => $error,
        ]);
    }

    /**
     * Mark an error as resolved.
     */
    public function resolve(Request $request, int $id)
    {
        $note = $request->get('note');
        $userId = auth()->id();

        if ($this->repository->resolve($id, $userId, $note)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Error marked as resolved']);
            }

            return back()->with('success', 'Error marked as resolved');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Error not found'], 404);
        }

        return back()->with('error', 'Error not found');
    }

    /**
     * Mark an error as unresolved.
     */
    public function unresolve(Request $request, int $id)
    {
        if ($this->repository->unresolve($id)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Error marked as unresolved']);
            }

            return back()->with('success', 'Error marked as unresolved');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Error not found'], 404);
        }

        return back()->with('error', 'Error not found');
    }

    /**
     * Delete an error.
     */
    public function destroy(Request $request, int $id)
    {
        if ($this->repository->delete($id)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Error deleted']);
            }

            return redirect()->route('log-notifier.dashboard')->with('success', 'Error deleted');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Error not found'], 404);
        }

        return back()->with('error', 'Error not found');
    }

    /**
     * Bulk actions on errors.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:resolve,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $action = $request->get('action');
        $ids = $request->get('ids');
        $userId = auth()->id();

        $count = match ($action) {
            'resolve' => $this->repository->bulkResolve($ids, $userId),
            'delete' => $this->repository->bulkDelete($ids),
            default => 0,
        };

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} error(s) {$action}d",
                'count' => $count,
            ]);
        }

        return back()->with('success', "{$count} error(s) {$action}d");
    }

    /**
     * Get statistics for API.
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 7);
        $statistics = $this->repository->getStatistics((int) $days);

        return response()->json($statistics);
    }

    /**
     * Get recent errors since a given timestamp (for polling/fallback).
     * NOTE: This is only used as a fallback if SSE is disabled.
     * Default behavior uses SSE stream endpoint which is real-time.
     * Routes to appropriate source based on configuration.
     */
    public function recent(Request $request)
    {
        $since = $request->get('since');

        return $this->recentFromDatabase($since);
    }

    /**
     * Server-Sent Events stream for real-time alerts.
     * Clients connect via EventSource and receive alerts without polling.
     */
    /**
     * Server-Sent Events stream for real-time alerts.
     * Sends pending errors and closes connection quickly.
     * Client will automatically reconnect.
     */
    public function stream(Request $request)
    {
        // Set proper headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Prevent output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        try {
            // Get Last-Event-ID from request header
            $lastEventId = (int) ($request->header('Last-Event-ID') ?? 0);

            // Send initial connection message
            echo ": Connected\n\n";
            flush();

            // Send any pending errors
            $pendingErrors = $this->getPendingErrors($lastEventId);

            if (! empty($pendingErrors)) {
                foreach ($pendingErrors as $error) {
                    echo "id: {$error['id']}\n";
                    echo 'data: '.json_encode($error)."\n\n";
                    flush();
                }
            }

            // Keep connection alive for 25 seconds (safely under 30s PHP timeout)
            // Send heartbeat every 5 seconds
            $startTime = time();
            $maxDuration = 25; // seconds
            $heartbeatInterval = 5; // seconds
            $lastHeartbeat = $startTime;

            while ((time() - $startTime) < $maxDuration) {
                if (connection_aborted()) {
                    break;
                }

                $currentTime = time();
                if ($currentTime - $lastHeartbeat >= $heartbeatInterval) {
                    echo ": ping\n\n";
                    flush();
                    $lastHeartbeat = $currentTime;
                }

                usleep(100000); // 0.1 seconds - small sleep to reduce CPU usage
            }

            // Close connection gracefully
            echo "event: close\n";
            echo "data: Connection timeout\n\n";
            flush();
        } catch (\Exception $e) {
            // Log error but don't output it (would break SSE protocol)
            error_log('[Log Notifier Stream Error] '.$e->getMessage());

            // Send error event
            echo "event: error\n";
            echo "data: Stream error\n\n";
            flush();
        }

        exit;
    }

    /**
     * Get pending errors since last event ID
     * Only retrieves errors that haven't been sent yet
     */
    protected function getPendingErrors($lastEventId = 0): array
    {
        try {
            $useDatabase = config('log-notifier.store_in_database', true);

            if (! $useDatabase) {
                // File mode - return empty, new errors will be picked up on next poll
                return [];
            }

            // Database mode - get new errors
            $modelClass = \Irabbi360\LaravelLogNotifier\Models\LogError::class;

            if (! class_exists($modelClass)) {
                error_log('[Log Notifier] LogError model not found');

                return [];
            }

            $errors = $modelClass::query()
                ->where('id', '>', $lastEventId)
                ->orderBy('id', 'asc')
                ->limit(10)
                ->get(['id', 'level', 'message', 'file', 'line', 'last_occurred_at']);

            if (! $errors || $errors->isEmpty()) {
                return [];
            }

            return $errors->map(function ($error) {
                return [
                    'id' => (int) $error->id,
                    'level' => (string) $error->level,
                    'message' => \Illuminate\Support\Str::limit((string) $error->message, 200),
                    'file' => (string) $error->file,
                    'line' => (int) $error->line,
                    'occurred_at' => $error->last_occurred_at ? $error->last_occurred_at->toIso8601String() : now()->toIso8601String(),
                ];
            })->toArray();
        } catch (\Throwable $e) {
            // Log detailed error for debugging
            error_log('[Log Notifier Stream] Error fetching pending errors: '.$e->getMessage());
            error_log('[Log Notifier Stream] File: '.$e->getFile().' Line: '.$e->getLine());

            // Return empty array to keep stream alive
            return [];
        }
    }

    /**
     * Get errors for streaming (newer than lastEventId)
     */
    protected function getStreamErrors($lastEventId = 0): array
    {
        try {
            $useDatabase = config('log-notifier.store_in_database', true);

            if ($useDatabase) {
                return \Irabbi360\LaravelLogNotifier\Models\LogError::query()
                    ->where('id', '>', $lastEventId)
                    ->orderBy('id', 'asc')
                    ->limit(10)
                    ->get(['id', 'level', 'message', 'file', 'line', 'last_occurred_at'])
                    ->map(function ($error) {
                        return [
                            'id' => $error->id,
                            'level' => $error->level,
                            'message' => \Illuminate\Support\Str::limit($error->message, 200),
                            'file' => $error->file,
                            'line' => $error->line,
                            'occurred_at' => $error->last_occurred_at->toIso8601String(),
                        ];
                    })
                    ->toArray();
            } else {
                // For file mode, use cache with timestamps
                $errors = ErrorCache::getAll();

                return array_map(function ($error) {
                    return [
                        'id' => $error['id'] ?? uniqid(),
                        'level' => $error['level'] ?? 'error',
                        'message' => \Illuminate\Support\Str::limit($error['message'] ?? '', 200),
                        'file' => $error['file'] ?? 'unknown',
                        'line' => $error['line'] ?? 0,
                        'occurred_at' => $error['occurred_at'] ?? now()->toIso8601String(),
                    ];
                }, array_slice($errors, 0, 10));
            }
        } catch (\Exception $e) {
            // Log error and return empty array to keep stream alive
            if (config('log-notifier.debug')) {
                error_log('[Log Notifier SSE Error] '.$e->getMessage());
            }

            return [];
        }
    }

    /**
     * Get recent errors from database.
     */
    protected function recentFromDatabase(?string $since)
    {
        $query = LogError::query();

        if ($since) {
            $query->where('created_at', '>', Carbon::parse($since));
        }

        return $query
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn ($error) => [
                'id' => $error->id,
                'level' => $error->level,
                'message' => $error->message,
                'file' => $error->file,
                'line' => $error->line,
                'occurred_at' => $error->last_occurred_at?->toIso8601String(),
            ])
            ->toArray();
    }
}
