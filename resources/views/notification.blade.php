<!-- Log Notifier Global Toast Container -->
<div id="log-notifier-container" class="fixed top-4 right-4 z-50"></div>

<!-- Global Toast Styles -->
<style>
    .log-notifier-toast {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        margin-bottom: 12px;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .log-notifier-toast:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .log-notifier-toast.emergency {
        border-left: 4px solid #7f1d1d;
        background: #fef2f2;
    }

    .log-notifier-toast.alert {
        border-left: 4px solid #b91c1c;
        background: #fee2e2;
    }

    .log-notifier-toast.critical {
        border-left: 4px solid #dc2626;
        background: #fecaca;
    }

    .log-notifier-toast.error {
        border-left: 4px solid #ea580c;
        background: #ffedd5;
    }

    .log-notifier-toast.warning {
        border-left: 4px solid #f59e0b;
        background: #fef3c7;
    }

    .log-notifier-toast.success {
        border-left: 4px solid #10b981;
        background: #ecfdf5;
    }

    .log-notifier-toast.info {
        border-left: 4px solid #3b82f6;
        background: #eff6ff;
    }

    .log-notifier-toast-icon {
        font-size: 20px;
        flex-shrink: 0;
    }

    .log-notifier-toast-content {
        flex: 1;
        min-width: 0;
    }

    .log-notifier-toast-title {
        font-weight: 600;
        font-size: 14px;
        margin: 0 0 4px 0;
        color: #1f2937;
    }

    .log-notifier-toast-message {
        font-size: 13px;
        color: #4b5563;
        margin: 0;
        word-break: break-word;
        white-space: normal;
    }

    .log-notifier-toast-close {
        font-size: 18px;
        cursor: pointer;
        flex-shrink: 0;
        color: #9ca3af;
        transition: color 0.2s;
    }

    .log-notifier-toast-close:hover {
        color: #6b7280;
    }

    .log-notifier-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 40;
        background: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .log-notifier-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    .log-notifier-toggle.disabled {
        opacity: 0.5;
    }

    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .log-notifier-toast.removing {
        animation: slideOut 0.3s ease-out;
    }
</style>

<!-- Global Toast JavaScript -->
<script>
const GlobalToast = {
    container: null,
    lastChecked: new Date().toISOString(),
    eventSource: null,
    pollInterval: null,
    enabled: localStorage.getItem('logNotifierToasts') !== 'false',
    dashboardRoute: '{{ config('log-notifier.dashboard_route', '/log-notifier') }}',
    useSSE: {{ config('log-notifier.use_sse', true) ? 'true' : 'false' }},
    
    init() {
        // Create container if not exists
        this.container = document.getElementById('log-notifier-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'log-notifier-container';
            this.container.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(this.container);
        }

        // Create toggle button
        if (!document.getElementById('log-notifier-toggle')) {
            const toggle = document.createElement('button');
            toggle.id = 'log-notifier-toggle';
            toggle.className = 'log-notifier-toggle' + (this.enabled ? '' : ' disabled');
            toggle.innerHTML = '🔔';
            toggle.title = this.enabled ? 'Notifications enabled' : 'Notifications disabled';
            toggle.addEventListener('click', () => this.toggle());
            document.body.appendChild(toggle);
        }

        // Start real-time stream or polling
        if (this.enabled) {
            if (this.useSSE) {
                this.startSSE();
            } else {
                this.startPolling();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (this.enabled) {
                if (this.useSSE) {
                    this.startSSE();
                } else {
                    this.startPolling();
                }
            }
        });
    },

    show(message, type = 'info', duration = 5000, onClick = null) {
        const toast = document.createElement('div');
        toast.className = `log-notifier-toast ${type}`;

        const icons = {
            emergency: '🚨',
            alert: '⚠️',
            critical: '🔴',
            error: '❌',
            warning: '⚠️',
            success: '✅',
            info: 'ℹ️'
        };

        toast.innerHTML = `
            <span class="log-notifier-toast-icon">${icons[type] || '📢'}</span>
            <div class="log-notifier-toast-content">
                <p class="log-notifier-toast-title">${type.charAt(0).toUpperCase() + type.slice(1)}</p>
                <p class="log-notifier-toast-message">${this.escapeHtml(message)}</p>
            </div>
            <span class="log-notifier-toast-close">✕</span>
        `;

        const closeBtn = toast.querySelector('.log-notifier-toast-close');
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.removeToast(toast);
        });

        if (onClick) {
            toast.addEventListener('click', onClick);
        }

        this.container.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => this.removeToast(toast), duration);
        }

        // Play sound for errors
        if (['error', 'critical', 'alert', 'emergency'].includes(type)) {
            this.playSound();
        }

        return toast;
    },

    removeToast(toast) {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    },

    startSSE() {
        // Close existing connection if any
        if (this.eventSource) {
            this.eventSource.close();
        }

        // Connect to Server-Sent Events stream
        this.eventSource = new EventSource(`${this.dashboardRoute}/api/stream`);

        // Handle new errors
        this.eventSource.addEventListener('message', (event) => {
            try {
                // Skip connection messages and heartbeats (they start with :)
                if (event.data && !event.data.startsWith(':')) {
                    const error = JSON.parse(event.data);
                    this.displayError(error);
                }
            } catch (e) {
                // Silently skip non-JSON messages (like heartbeats)
            }
        });

        // Handle errors
        this.eventSource.addEventListener('error', (e) => {
            console.warn('[Log Notifier] SSE connection closed');
            this.eventSource.close();
            // Reconnect after 3 seconds (longer interval for normal close)
            if (this.enabled) {
                setTimeout(() => this.startSSE(), 3000);
            }
        });

        // Handle stream close
        this.eventSource.addEventListener('close', (e) => {
            console.log('[Log Notifier] Server closed connection, reconnecting...');
            this.eventSource.close();
            // Reconnect after brief delay (server is closing normally)
            if (this.enabled) {
                setTimeout(() => this.startSSE(), 2000);
            }
        });
    },

    stopSSE() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    },

    displayError(error) {
        const typeMap = {
            'emergency': 'emergency',
            'alert': 'alert',
            'critical': 'critical',
            'error': 'error',
            'warning': 'warning',
            'notice': 'info',
            'info': 'info',
            'debug': 'info'
        };

        const type = typeMap[error.level] || 'info';
        const message = error.message.substring(0, 100);

        this.show(
            message,
            type,
            5000,
            () => {
                window.location.href = `${this.dashboardRoute}/errors/${error.id}`;
            }
        );
    },

    toggle() {
        this.enabled = !this.enabled;
        localStorage.setItem('logNotifierToasts', this.enabled ? 'true' : 'false');

        const toggle = document.getElementById('log-notifier-toggle');
        if (this.enabled) {
            toggle.classList.remove('disabled');
            toggle.title = 'Notifications enabled';
            this.show('Notifications enabled ✓', 'success', 3000);
            this.startSSE();
        } else {
            toggle.classList.add('disabled');
            toggle.title = 'Notifications disabled';
            this.show('Notifications disabled', 'info', 2000);
            this.stopSSE();
        }
    },

    playSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 1000;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            // Silent fail if audio context not available
        }
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => GlobalToast.init());
} else {
    GlobalToast.init();
}
</script>
