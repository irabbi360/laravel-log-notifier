<?php

namespace Irabbi360\LaravelLogNotifier\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Irabbi360\LaravelLogNotifier\Models\PushSubscription;

class NotificationController extends Controller
{
    /**
     * Subscribe to push notifications.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $subscription = PushSubscription::createOrUpdateSubscription(
            $request->all(),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Unsubscribe from push notifications.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $deleted = PushSubscription::where('endpoint', $request->endpoint)->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Unsubscribed successfully' : 'Subscription not found',
        ]);
    }

    /**
     * Update subscription preferences.
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'levels' => 'array',
            'levels.*' => 'string|in:emergency,alert,critical,error,warning,notice,info,debug',
        ]);

        $subscription = PushSubscription::where('endpoint', $request->endpoint)->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found',
            ], 404);
        }

        $subscription->updateLevels($request->levels ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }

    /**
     * Get VAPID public key.
     */
    public function vapidPublicKey()
    {
        return response()->json([
            'publicKey' => config('log-notifier.vapid.public_key'),
        ]);
    }

    /**
     * Get current subscription status.
     */
    public function status(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $subscription = PushSubscription::where('endpoint', $request->endpoint)->first();

        if (! $subscription) {
            return response()->json([
                'subscribed' => false,
            ]);
        }

        return response()->json([
            'subscribed' => true,
            'is_active' => $subscription->is_active,
            'levels' => $subscription->subscribed_levels,
        ]);
    }
}
