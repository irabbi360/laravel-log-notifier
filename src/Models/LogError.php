<?php

namespace Irabbi360\LaravelLogNotifier\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogError extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'context' => 'array',
        'request_data' => 'array',
        'is_resolved' => 'boolean',
        'first_occurred_at' => 'datetime',
        'last_occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('log-notifier.tables.errors', 'log_notifier_errors'));
    }

    public function getConnectionName(): ?string
    {
        return config('log-notifier.database_connection') ?? parent::getConnectionName();
    }

    /**
     * Get the user who resolved the error.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'resolved_by');
    }

    /**
     * Scope for unresolved errors.
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for resolved errors.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Scope for specific log level.
     */
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', strtolower($level));
    }

    /**
     * Scope for critical levels (emergency, alert, critical, error).
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('level', ['emergency', 'alert', 'critical', 'error']);
    }

    /**
     * Scope for errors within a date range.
     */
    public function scopeBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('last_occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent errors.
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_occurred_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for searching errors.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
                ->orWhere('file', 'like', "%{$search}%")
                ->orWhere('trace', 'like', "%{$search}%");
        });
    }

    /**
     * Mark the error as resolved.
     */
    public function markAsResolved(?int $userId = null, ?string $note = null): bool
    {
        return $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_note' => $note,
        ]);
    }

    /**
     * Mark the error as unresolved.
     */
    public function markAsUnresolved(): bool
    {
        return $this->update([
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_note' => null,
        ]);
    }

    /**
     * Increment the occurrence count.
     */
    public function incrementOccurrence(): bool
    {
        return $this->update([
            'occurrence_count' => $this->occurrence_count + 1,
            'last_occurred_at' => now(),
        ]);
    }

    /**
     * Get the level badge color.
     */
    public function getLevelColorAttribute(): string
    {
        return match ($this->level) {
            'emergency' => 'red',
            'alert' => 'orange',
            'critical' => 'red',
            'error' => 'yellow',
            'warning' => 'amber',
            'notice' => 'blue',
            'info' => 'cyan',
            'debug' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get a short excerpt of the message.
     */
    public function getExcerptAttribute(): string
    {
        return \Illuminate\Support\Str::limit($this->message, 100);
    }

    /**
     * Get formatted file path with line number.
     */
    public function getLocationAttribute(): string
    {
        if (empty($this->file)) {
            return 'Unknown';
        }

        $file = str_replace(base_path().'/', '', $this->file);

        return $this->line ? "{$file}:{$this->line}" : $file;
    }
}
