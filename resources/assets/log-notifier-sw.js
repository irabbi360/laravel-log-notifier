/**
 * Laravel Log Notifier Service Worker
 * Handles push notifications for error alerts
 */

const CACHE_NAME = 'log-notifier-v1';

// Install event
self.addEventListener('install', (event) => {
    console.log('[Log Notifier SW] Installing...');
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    console.log('[Log Notifier SW] Activating...');
    event.waitUntil(clients.claim());
});

// Push event - Handle incoming push notifications
self.addEventListener('push', (event) => {
    console.log('[Log Notifier SW] Push received');

    let data = {
        title: 'Laravel Error 🚨',
        body: 'An error occurred in your application',
        icon: '/vendor/log-notifier/icon.png',
        badge: '/vendor/log-notifier/badge.png',
        vibrate: [100, 50, 100],
        requireInteraction: true,
        tag: 'log-notifier-' + Date.now(),
        data: {}
    };

    if (event.data) {
        try {
            const payload = event.data.json();
            data = { ...data, ...payload };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        vibrate: data.vibrate,
        requireInteraction: data.requireInteraction,
        tag: data.tag,
        data: data.data,
        actions: data.actions || [
            { action: 'view', title: 'View Details' },
            { action: 'dismiss', title: 'Dismiss' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[Log Notifier SW] Notification clicked');

    const notification = event.notification;
    const action = event.action;
    const data = notification.data || {};

    notification.close();

    if (action === 'dismiss') {
        return;
    }

    // Default action or 'view' action
    const urlToOpen = data.url || '/log-notifier';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window/tab open with the target URL
                for (const client of clientList) {
                    if (client.url.includes('/log-notifier') && 'focus' in client) {
                        client.navigate(urlToOpen);
                        return client.focus();
                    }
                }
                // If no existing window, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('[Log Notifier SW] Notification closed');
});

// Handle resolve action
self.addEventListener('notificationclick', (event) => {
    if (event.action === 'resolve' && event.notification.data?.id) {
        const errorId = event.notification.data.id;
        
        event.waitUntil(
            fetch(`/log-notifier/errors/${errorId}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(response => {
                if (response.ok) {
                    console.log('[Log Notifier SW] Error marked as resolved');
                }
            }).catch(error => {
                console.error('[Log Notifier SW] Failed to resolve error:', error);
            })
        );
    }
});

// Message event - Handle messages from the main thread
self.addEventListener('message', (event) => {
    console.log('[Log Notifier SW] Message received:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
