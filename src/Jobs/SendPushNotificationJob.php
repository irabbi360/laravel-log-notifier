<?php

namespace Irabbi360\LaravelLogNotifier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Irabbi360\LaravelLogNotifier\Models\PushSubscription;
use Irabbi360\LaravelLogNotifier\Services\PushNotifier;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public PushSubscription $subscription,
        public array $payload
    ) {}

    public function handle(PushNotifier $notifier): void
    {
        $notifier->sendNotification($this->subscription, $this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        // Optionally deactivate subscription after repeated failures
        if ($this->attempts() >= $this->tries) {
            $this->subscription->deactivate();
        }
    }
}
