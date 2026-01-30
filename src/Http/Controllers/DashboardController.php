<?php

namespace Irabbi360\LaravelLogNotifier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Irabbi360\LaravelLogNotifier\Services\ErrorRepository;

class DashboardController extends Controller
{
    protected ErrorRepository $repository;

    public function __construct(ErrorRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display the error dashboard.
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

        $errors = $this->repository->getErrors(array_filter($filters), 20);
        $statistics = $this->repository->getStatistics();

        return view('log-notifier::dashboard.index', [
            'errors' => $errors,
            'statistics' => $statistics,
            'filters' => $filters,
            'levels' => config('log-notifier.levels', ['error', 'critical', 'alert', 'emergency']),
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
     * Get recent errors since a given timestamp (for toast notifications).
     */
    public function recent(Request $request)
    {
        $since = $request->get('since');

        $query = \Irabbi360\LaravelLogNotifier\Models\LogError::query()
            ->where('is_resolved', false)
            ->orderBy('last_occurred_at', 'desc')
            ->limit(10);

        if ($since) {
            try {
                $sinceDate = \Carbon\Carbon::parse($since);
                $query->where('last_occurred_at', '>', $sinceDate);
            } catch (\Exception $e) {
                // Invalid date, ignore filter
            }
        }

        $errors = $query->get(['id', 'level', 'message', 'file', 'line', 'last_occurred_at']);

        return response()->json([
            'errors' => $errors->map(function ($error) {
                return [
                    'id' => $error->id,
                    'level' => $error->level,
                    'message' => \Illuminate\Support\Str::limit($error->message, 200),
                    'file' => $error->file,
                    'line' => $error->line,
                    'occurred_at' => $error->last_occurred_at->toIso8601String(),
                ];
            }),
            'timestamp' => now()->toIso8601String(),
            'count' => $errors->count(),
        ]);
    }
}
