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
        
        /* Toast animations */
        .toast-enter {
            animation: toast-in 0.3s ease-out;
        }
        .toast-leave {
            animation: toast-out 0.3s ease-in forwards;
        }
        @keyframes toast-in {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toast-out {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col space-y-2 max-w-sm"></div>

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
                    <!-- Status indicator -->
                    <span class="text-xs text-green-600 font-medium">
                        🔔 Live
                    </span>
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
        // Toast notification system
        const Toast = {
            container: null,
            
            init() {
                this.container = document.getElementById('toast-container');
            },
            
            show(message, type = 'error', duration = 5000, onClick = null) {
                const toast = document.createElement('div');
                toast.className = `toast-enter p-4 rounded-lg shadow-lg cursor-pointer transform transition-all ${this.getTypeClasses(type)}`;
                toast.innerHTML = `
                    <div class="flex items-start">
                        <div class="flex-shrink-0">${this.getIcon(type)}</div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium">${this.getTitle(type)}</p>
                            <p class="mt-1 text-sm opacity-90">${this.escapeHtml(message)}</p>
                        </div>
                        <button class="ml-4 flex-shrink-0 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                `;
                
                if (onClick) {
                    toast.addEventListener('click', (e) => {
                        if (!e.target.closest('button')) {
                            onClick();
                        }
                    });
                }
                
                this.container.appendChild(toast);
                
                // Play sound for errors
                if (type === 'error' || type === 'critical') {
                    this.playSound();
                }
                
                // Auto remove
                setTimeout(() => {
                    toast.classList.remove('toast-enter');
                    toast.classList.add('toast-leave');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            },
            
            getTypeClasses(type) {
                const classes = {
                    emergency: 'bg-red-600 text-white',
                    alert: 'bg-orange-500 text-white',
                    critical: 'bg-red-500 text-white',
                    error: 'bg-red-100 text-red-800 border border-red-300',
                    warning: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
                    success: 'bg-green-100 text-green-800 border border-green-300',
                    info: 'bg-blue-100 text-blue-800 border border-blue-300',
                };
                return classes[type] || classes.error;
            },
            
            getIcon(type) {
                const icons = {
                    emergency: '🚨',
                    alert: '⚠️',
                    critical: '💥',
                    error: '❌',
                    warning: '⚡',
                    success: '✅',
                    info: 'ℹ️',
                };
                return icons[type] || '❌';
            },
            
            getTitle(type) {
                const titles = {
                    emergency: 'Emergency!',
                    alert: 'Alert!',
                    critical: 'Critical Error!',
                    error: 'Error Detected',
                    warning: 'Warning',
                    success: 'Success',
                    info: 'Info',
                };
                return titles[type] || 'Notification';
            },
            
            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },
            
            playSound() {
                // Create a simple beep sound using Web Audio API
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 800;
                    oscillator.type = 'sine';
                    gainNode.gain.value = 0.1;
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.15);
                } catch (e) {
                    // Audio not supported
                }
            }
        };
        
        Toast.init();
    </script>

    <!-- Global Log Notifier Toast Notifications -->
    {!! \Irabbi360\LaravelLogNotifier\Facades\LaravelLogNotifier::notification() !!}

    @stack('scripts')
</body>
</html>
