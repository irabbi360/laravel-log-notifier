<?php

namespace Irabbi360\LaravelLogNotifier\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Irabbi360\LaravelLogNotifier\Jobs\SendPushNotificationJob;
use Irabbi360\LaravelLogNotifier\Models\LogError;
use Irabbi360\LaravelLogNotifier\Models\PushSubscription;

class PushNotifier
{
    /**
     * Send notification for an error.
     */
    public function notify(LogError $error): void
    {
        if (! config('log-notifier.enabled', true)) {
            return;
        }

        $subscriptions = PushSubscription::active()
            ->forLevel($error->level)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = $this->buildPayload($error);

        foreach ($subscriptions as $subscription) {
            if (config('log-notifier.queue.enabled', false)) {
                SendPushNotificationJob::dispatch($subscription, $payload)
                    ->onConnection(config('log-notifier.queue.connection'))
                    ->onQueue(config('log-notifier.queue.queue'));
            } else {
                $this->sendNotification($subscription, $payload);
            }
        }
    }

    /**
     * Build the notification payload.
     */
    protected function buildPayload(LogError $error): array
    {
        $config = config('log-notifier.notification', []);
        $dashboardRoute = config('log-notifier.dashboard_route', '/log-notifier');

        return [
            'title' => $config['title'] ?? 'Laravel Error 🚨',
            'body' => $this->truncate($error->message, 200),
            'icon' => $config['icon'] ?? '/vendor/log-notifier/icon.png',
            'badge' => $config['badge'] ?? '/vendor/log-notifier/badge.png',
            'vibrate' => $config['vibrate'] ?? [100, 50, 100],
            'requireInteraction' => $config['require_interaction'] ?? true,
            'tag' => 'log-error-'.$error->id,
            'data' => [
                'id' => $error->id,
                'level' => $error->level,
                'url' => url($dashboardRoute.'/errors/'.$error->id),
                'timestamp' => $error->last_occurred_at->toIso8601String(),
            ],
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Details',
                ],
                [
                    'action' => 'resolve',
                    'title' => 'Mark Resolved',
                ],
            ],
        ];
    }

    /**
     * Send notification to a subscription.
     */
    public function sendNotification(PushSubscription $subscription, array $payload): bool
    {
        try {
            $vapidPublicKey = config('log-notifier.vapid.public_key');
            $vapidPrivateKey = config('log-notifier.vapid.private_key');
            $vapidSubject = config('log-notifier.vapid.subject');

            if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
                Log::warning('Log Notifier: VAPID keys not configured');

                return false;
            }

            $auth = $this->createVapidAuth($subscription, $vapidPublicKey, $vapidPrivateKey, $vapidSubject);
            $encryptedPayload = $this->encryptPayload($subscription, json_encode($payload));

            $response = Http::withHeaders([
                'Authorization' => $auth['authorization'],
                'Crypto-Key' => $auth['crypto_key'],
                'Content-Encoding' => 'aesgcm',
                'Content-Type' => 'application/octet-stream',
                'TTL' => 86400,
                'Urgency' => 'high',
            ])->withBody($encryptedPayload, 'application/octet-stream')
                ->post($subscription->endpoint);

            if ($response->successful() || $response->status() === 201) {
                $subscription->markAsUsed();

                return true;
            }

            // Handle subscription expiration
            if ($response->status() === 404 || $response->status() === 410) {
                $subscription->deactivate();
                Log::info('Log Notifier: Subscription deactivated (expired)', ['id' => $subscription->id]);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Log Notifier: Failed to send push notification', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscription->id,
            ]);

            return false;
        }
    }

    /**
     * Create VAPID authentication headers.
     * Note: This is a simplified implementation. For production, consider using a library like web-push-php.
     */
    protected function createVapidAuth(PushSubscription $subscription, string $publicKey, string $privateKey, string $subject): array
    {
        // Parse the endpoint URL
        $parsedUrl = parse_url($subscription->endpoint);
        $audience = $parsedUrl['scheme'].'://'.$parsedUrl['host'];

        // Create JWT token
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 86400,
            'sub' => $subject,
        ]));

        $unsignedToken = $header.'.'.$payload;

        // Sign with ECDSA - simplified for demonstration
        // In production, use a proper JWT library
        $signature = $this->signWithECDSA($unsignedToken, $privateKey);
        $jwt = $unsignedToken.'.'.$signature;

        return [
            'authorization' => 'vapid t='.$jwt.', k='.$publicKey,
            'crypto_key' => 'p256ecdsa='.$publicKey,
        ];
    }

    /**
     * Sign data with ECDSA.
     */
    protected function signWithECDSA(string $data, string $privateKey): string
    {
        // This is a placeholder - in production use openssl or a dedicated library
        // For full implementation, consider using minishlink/web-push package
        return $this->base64UrlEncode(hash('sha256', $data.$privateKey, true));
    }

    /**
     * Encrypt the payload for Web Push.
     * Note: This is simplified. For production, use web-push-php library.
     */
    protected function encryptPayload(PushSubscription $subscription, string $payload): string
    {
        // This is a placeholder for the actual encryption implementation
        // Web Push requires specific encryption using the subscription's public key
        // For full implementation, consider using minishlink/web-push package
        return $payload;
    }

    /**
     * Base64 URL encode.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Truncate a string.
     */
    protected function truncate(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        return substr($string, 0, $length - 3).'...';
    }

    /**
     * Send a test notification to all active subscriptions.
     */
    public function sendTestNotification(): int
    {
        $subscriptions = PushSubscription::active()->get();
        $sent = 0;

        $payload = [
            'title' => 'Test Notification 🧪',
            'body' => 'Laravel Log Notifier is working correctly!',
            'icon' => config('log-notifier.notification.icon', '/vendor/log-notifier/icon.png'),
            'badge' => config('log-notifier.notification.badge', '/vendor/log-notifier/badge.png'),
            'tag' => 'log-notifier-test-'.time(),
            'data' => [
                'test' => true,
                'url' => url(config('log-notifier.dashboard_route', '/log-notifier')),
            ],
        ];

        foreach ($subscriptions as $subscription) {
            if ($this->sendNotification($subscription, $payload)) {
                $sent++;
            }
        }

        return $sent;
    }
}
