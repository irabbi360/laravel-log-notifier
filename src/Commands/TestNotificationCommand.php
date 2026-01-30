<?php

namespace Irabbi360\LaravelLogNotifier\Commands;

use Illuminate\Console\Command;
use Irabbi360\LaravelLogNotifier\Models\PushSubscription;
use Irabbi360\LaravelLogNotifier\Services\PushNotifier;

class TestNotificationCommand extends Command
{
    protected $signature = 'log-notifier:test';

    protected $description = 'Send a test push notification to all active subscriptions';

    public function handle(PushNotifier $notifier): int
    {
        $subscriptionCount = PushSubscription::active()->count();

        if ($subscriptionCount === 0) {
            $this->warn('No active push subscriptions found.');
            $this->line('Visit the dashboard to subscribe: '.url(config('log-notifier.dashboard_route', '/log-notifier')));

            return self::SUCCESS;
        }

        $this->info("Sending test notification to {$subscriptionCount} subscription(s)...");

        $sent = $notifier->sendTestNotification();

        if ($sent > 0) {
            $this->info("✅ Successfully sent {$sent} notification(s).");
        } else {
            $this->error('Failed to send notifications. Check your VAPID configuration.');
        }

        return $sent > 0 ? self::SUCCESS : self::FAILURE;
    }
}
