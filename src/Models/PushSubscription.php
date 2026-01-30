<?php

namespace Irabbi360\LaravelLogNotifier\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'subscribed_levels' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'auth_token',
        'public_key',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('log-notifier.tables.subscriptions', 'log_notifier_subscriptions'));
    }

    public function getConnectionName(): ?string
    {
        return config('log-notifier.database_connection') ?? parent::getConnectionName();
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'));
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for subscriptions that should receive a specific level.
     */
    public function scopeForLevel(Builder $query, string $level): Builder
    {
        return $query->where(function ($q) use ($level) {
            $q->whereNull('subscribed_levels')
                ->orWhereJsonContains('subscribed_levels', $level);
        });
    }

    /**
     * Get the subscription as a Web Push compatible array.
     */
    public function toWebPushArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->public_key,
                'auth' => $this->auth_token,
            ],
            'contentEncoding' => $this->content_encoding,
        ];
    }

    /**
     * Create or update a subscription.
     */
    public static function createOrUpdateSubscription(array $data, ?int $userId = null): self
    {
        $subscription = self::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $userId,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent' => $data['userAgent'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return $subscription;
    }

    /**
     * Mark the subscription as inactive (failed to send).
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Mark the subscription as used.
     */
    public function markAsUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Update subscribed levels.
     */
    public function updateLevels(array $levels): bool
    {
        return $this->update(['subscribed_levels' => $levels]);
    }
}
