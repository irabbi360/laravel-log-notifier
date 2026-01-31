/**
 * Global Toast Notification System for Log Notifier
 * 
 * This file provides a global toast notification system that displays
 * real-time error alerts across your entire application.
 * 
 * Usage: Include this script in your main layout
 */

const GlobalToast = {
    container: null,
    lastChecked: new Date().toISOString(),
    pollInterval: null,
    enabled: localStorage.getItem('logNotifierToasts') !== 'false',

    init() {
        // Get or create toast container
        this.container = document.getElementById('global-toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'global-toast-container';
            this.container.className = 'fixed top-4 right-4 z-50 flex flex-col space-y-2 max-w-sm pointer-events-none';
            document.body.appendChild(this.container);
        }

        // Start polling if enabled
        if (this.enabled) {
            this.startPolling();
        }

        // Listen for toggle events
        window.addEventListener('logNotifierToggle', (e) => {
            if (e.detail.enabled) {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        });
    },

    show(message, type = 'error', duration = 5000, onClick = null) {
        const toast = document.createElement('div');
        toast.className = `toast-enter p-4 rounded-lg shadow-lg cursor-pointer transform transition-all pointer-events-auto ${this.getTypeClasses(type)}`;
        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0 text-lg mr-3">${this.getIcon(type)}</div>
                <div class="flex-1">
                    <p class="text-sm font-medium">${this.getTitle(type)}</p>
                    <p class="mt-1 text-sm opacity-90">${this.escapeHtml(message)}</p>
                </div>
                <button class="ml-4 flex-shrink-0 text-current opacity-70 hover:opacity-100 transition-opacity" onclick="this.parentElement.parentElement.remove()">
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

        // Play sound for critical errors
        if (type === 'error' || type === 'critical' || type === 'emergency' || type === 'alert') {
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
    },

    startPolling() {
        this.enabled = true;
        localStorage.setItem('logNotifierToasts', 'true');

        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }

        // Poll every 10 seconds
        this.pollInterval = setInterval(() => this.checkForNewErrors(), 10000);

        // Check immediately
        this.checkForNewErrors();
    },

    stopPolling() {
        this.enabled = false;
        localStorage.setItem('logNotifierToasts', 'false');

        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    async checkForNewErrors() {
        try {
            const response = await fetch(
                `/log-notifier/api/recent?since=${encodeURIComponent(this.lastChecked)}`
            );

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.errors && data.errors.length > 0) {
                data.errors.forEach(error => {
                    const message = error.message.substring(0, 150) +
                        (error.message.length > 150 ? '...' : '');

                    this.show(
                        message,
                        error.level,
                        8000,
                        () => {
                            // Navigate to error details
                            const dashboardRoute = this.getDashboardRoute();
                            window.location.href = `${dashboardRoute}/errors/${error.id}`;
                        }
                    );
                });
            }

            this.lastChecked = data.timestamp || new Date().toISOString();
        } catch (error) {
            // Silently fail - don't break the app
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.error('Log Notifier polling error:', error);
            }
        }
    },

    getDashboardRoute() {
        // Try to get from document attribute, otherwise default
        return document.documentElement.getAttribute('data-log-notifier-route') || '/log-notifier';
    },

    toggle() {
        if (this.enabled) {
            this.stopPolling();
            this.show('Toast notifications disabled', 'info', 2000);
        } else {
            this.startPolling();
            this.show('Toast notifications enabled', 'success', 2000);
        }
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => GlobalToast.init());
} else {
    GlobalToast.init();
}
