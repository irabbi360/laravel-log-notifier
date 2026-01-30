<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Log Notifier') - {{ config('app.name') }}</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('log-notifier.dashboard') }}" class="flex items-center">
                        <span class="text-2xl mr-2">🚨</span>
                        <span class="font-semibold text-xl text-gray-800">Log Notifier</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <div x-data="pushNotifications()" x-init="init()">
                        <button 
                            @click="toggleSubscription()"
                            :class="subscribed ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-500 hover:bg-blue-600'"
                            class="px-4 py-2 text-white text-sm font-medium rounded-lg transition-colors flex items-center"
                            :disabled="loading"
                        >
                            <span x-show="loading" class="mr-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            <span x-text="subscribed ? '🔔 Notifications On' : '🔕 Enable Notifications'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                Laravel Log Notifier &copy; {{ date('Y') }}
            </p>
        </div>
    </footer>

    <script>
        function pushNotifications() {
            return {
                subscribed: false,
                loading: false,
                subscription: null,

                async init() {
                    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                        console.log('Push notifications not supported');
                        return;
                    }

                    try {
                        const registration = await navigator.serviceWorker.register('/log-notifier-sw.js');
                        this.subscription = await registration.pushManager.getSubscription();
                        this.subscribed = !!this.subscription;
                    } catch (error) {
                        console.error('Failed to initialize push notifications:', error);
                    }
                },

                async toggleSubscription() {
                    this.loading = true;
                    
                    try {
                        if (this.subscribed) {
                            await this.unsubscribe();
                        } else {
                            await this.subscribe();
                        }
                    } catch (error) {
                        console.error('Failed to toggle subscription:', error);
                        alert('Failed to toggle notifications. Please try again.');
                    }
                    
                    this.loading = false;
                },

                async subscribe() {
                    const registration = await navigator.serviceWorker.ready;
                    
                    // Get VAPID public key
                    const response = await fetch('{{ route("log-notifier.vapid") }}');
                    const { publicKey } = await response.json();
                    
                    if (!publicKey) {
                        alert('Push notifications are not configured. Please set VAPID keys.');
                        return;
                    }

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: this.urlBase64ToUint8Array(publicKey)
                    });

                    // Send subscription to server
                    await fetch('{{ route("log-notifier.subscribe") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(subscription.toJSON())
                    });

                    this.subscription = subscription;
                    this.subscribed = true;
                },

                async unsubscribe() {
                    if (this.subscription) {
                        await fetch('{{ route("log-notifier.unsubscribe") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ endpoint: this.subscription.endpoint })
                        });

                        await this.subscription.unsubscribe();
                        this.subscription = null;
                        this.subscribed = false;
                    }
                },

                urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding)
                        .replace(/-/g, '+')
                        .replace(/_/g, '/');
                    
                    const rawData = window.atob(base64);
                    const outputArray = new Uint8Array(rawData.length);
                    
                    for (let i = 0; i < rawData.length; ++i) {
                        outputArray[i] = rawData.charCodeAt(i);
                    }
                    
                    return outputArray;
                }
            };
        }
    </script>

    @stack('scripts')
</body>
</html>
