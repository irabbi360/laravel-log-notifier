<!-- Log Notifier Global Toast Container -->
<div id="log-notifier-container" class="fixed top-4 right-4 z-50"></div>

<!-- Modal for error details -->
<div id="log-notifier-modal" class="log-notifier-modal">
    <div class="log-notifier-modal-content">
        <div class="log-notifier-modal-header">
            <h2 class="log-notifier-modal-title">Error Details</h2>
            <button class="log-notifier-modal-close" onclick="GlobalToast.closeModal()">&times;</button>
        </div>
        <div class="log-notifier-modal-body">
            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">Level</label>
                <div class="log-notifier-modal-field-value">
                    <span id="modal-level" class="log-notifier-level-badge"></span>
                </div>
            </div>

            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">Message</label>
                <div class="log-notifier-modal-field-value" id="modal-message"></div>
            </div>

            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">Stack Trace</label>
                <div class="log-notifier-modal-field-value" id="modal-trace"></div>
            </div>

            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">File</label>
                <div class="log-notifier-modal-field-value" id="modal-file"></div>
            </div>

            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">Line</label>
                <div class="log-notifier-modal-field-value" id="modal-line"></div>
            </div>

            <div class="log-notifier-modal-field">
                <label class="log-notifier-modal-field-label">Occurred At</label>
                <div class="log-notifier-modal-field-value" id="modal-time"></div>
            </div>
        </div>
    </div>
</div>

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

    /* Modal Styles - Laravel Error Page Style */
    .log-notifier-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        animation: fadeIn 0.3s ease-out;
        overflow-y: auto;
    }

    .log-notifier-modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .log-notifier-modal-content {
        background: #191e26;
        padding: 0;
        border-radius: 0;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        max-width: 900px;
        width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        animation: slideDown 0.3s ease-out;
        border: none;
        font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
    }

    .log-notifier-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0;
        padding: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #2d3748;
        background: linear-gradient(180deg, #1f2937 0%, #191e26 100%);
    }

    .log-notifier-modal-title {
        font-size: 28px;
        font-weight: 400;
        color: #f1f5f9;
        margin: 0;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .log-notifier-modal-title::before {
        content: "🚨";
        font-size: 32px;
        display: inline-block;
    }

    .log-notifier-modal-close {
        font-size: 32px;
        font-weight: 300;
        color: #8b949e;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }

    .log-notifier-modal-close:hover {
        color: #f1f5f9;
    }

    .log-notifier-modal-body {
        color: #c9d1d9;
        padding: 30px;
    }

    .log-notifier-modal-field {
        margin-bottom: 30px;
    }

    .log-notifier-modal-field:last-child {
        margin-bottom: 0;
    }

    .log-notifier-modal-field-label {
        font-weight: 600;
        color: #79c0ff;
        font-size: 13px;
        margin-bottom: 12px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .log-notifier-modal-field-value {
        background: #0d1117;
        padding: 16px;
        border-radius: 6px;
        font-family: 'Fira Code', 'Menlo', 'Monaco', 'Courier New', monospace;
        font-size: 13px;
        color: #c9d1d9;
        word-break: break-all;
        white-space: pre-wrap;
        overflow-x: auto;
        border: 1px solid #30363d;
        max-height: 300px;
        overflow-y: auto;
        line-height: 1.6;
    }

    .log-notifier-modal-field-value::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .log-notifier-modal-field-value::-webkit-scrollbar-track {
        background: #0f172a;
        border-radius: 3px;
    }

    .log-notifier-modal-field-value::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 3px;
    }

    .log-notifier-modal-field-value::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    #modal-message {
        background: #0d1117;
        padding: 20px;
        border-radius: 6px;
        font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
        font-size: 18px;
        color: #f1f5f9;
        word-break: break-word;
        white-space: pre-wrap;
        overflow-x: auto;
        border: 1px solid #30363d;
        max-height: 200px;
        overflow-y: auto;
        line-height: 1.6;
        font-weight: 500;
    }

    .json-key {
        color: #60a5fa;
    }

    .json-string {
        color: #34d399;
    }

    .json-number {
        color: #f87171;
    }

    .json-boolean {
        color: #fbbf24;
    }

    .json-null {
        color: #9ca3af;
    }

    .json-bracket {
        color: #cbd5e1;
    }

    #modal-trace {
        font-size: 12px;
        line-height: 1.7;
    }

    .log-notifier-code-line {
        display: block;
        padding: 2px 0;
    }

    .log-notifier-code-line-number {
        display: inline-block;
        width: 40px;
        text-align: right;
        color: #64748b;
        margin-right: 15px;
        user-select: none;
    }

    .log-notifier-level-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .log-notifier-level-error {
        background-color: #fee2e2;
        color: #b91c1c;
    }

    .log-notifier-level-critical {
        background-color: #fecaca;
        color: #dc2626;
    }

    .log-notifier-level-alert {
        background-color: #fecaca;
        color: #dc2626;
    }

    .log-notifier-level-emergency {
        background-color: #fecaca;
        color: #7f1d1d;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<!-- Global Toast JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    const GlobalToast = {
        container: null,
        lastChecked: new Date().toISOString(),
        eventSource: null,
        pollInterval: null,
        enabled: localStorage.getItem('logNotifierToasts') !== 'false',
        dashboardRoute: '{{ config('log-notifier.dashboard_route', '/log-notifier') }}',
        useSSE: {{ config('log-notifier.use_sse', true) ? 'true' : 'false' }},
        displayedErrors: {},
        initialized: false,

        init() {
            if (this.initialized) {
                return;
            }
            this.initialized = true;

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

            // Start SSE connection only once
            if (this.enabled) {
                this.startSSE();
            }
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

            // Store timeout reference and duration for pause/resume
            let timeoutId = null;
            let remainingTime = duration;
            let pausedAt = null;

            const startTimer = () => {
                timeoutId = setTimeout(() => this.removeToast(toast), remainingTime);
            };

            const pauseTimer = () => {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    pausedAt = Date.now();
                }
            };

            const resumeTimer = () => {
                if (pausedAt) {
                    const elapsed = Date.now() - pausedAt;
                    remainingTime = Math.max(0, remainingTime - elapsed);
                    pausedAt = null;
                    if (remainingTime > 0) {
                        startTimer();
                    } else {
                        this.removeToast(toast);
                    }
                }
            };

            // Add hover pause/resume functionality
            toast.addEventListener('mouseenter', pauseTimer);
            toast.addEventListener('mouseleave', resumeTimer);

            if (onClick) {
                toast.addEventListener('click', (e) => {
                    pauseTimer(); // Pause when clicking
                    onClick(e);
                });
            }

            this.container.appendChild(toast);

            if (duration > 0) {
                startTimer();
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

        startPolling() {
            // Not used - using SSE instead
        },

        stopPolling() {
            // Not used
        },

        startSSE() {
            // Close existing connection if any
            if (this.eventSource) {
                this.eventSource.close();
            }

            // Connect to Server-Sent Events stream
            const streamUrl = `${this.dashboardRoute}/api/stream`;
            this.eventSource = new EventSource(streamUrl);

            // Handle new errors
            this.eventSource.addEventListener('message', (event) => {
                try {
                    // Skip heartbeats and connection messages (comments start with :)
                    if (event.data && event.data.trim() && !event.data.startsWith(':')) {
                        const error = JSON.parse(event.data);
                        this.displayError(error);
                    }
                } catch (e) {
                    // Failed to parse message - ignore heartbeats
                }
            });

            // Handle connection open
            this.eventSource.addEventListener('open', (e) => {
                // Connection established - do nothing, just keep it alive
            });

            // Handle stream errors and reconnect with exponential backoff
            this.eventSource.addEventListener('error', (e) => {
                if (this.eventSource && this.eventSource.readyState === EventSource.CLOSED) {
                    // Stream closed - wait 5 seconds before reconnecting
                    if (this.enabled) {
                        setTimeout(() => {
                            if (this.enabled) {
                                this.startSSE();
                            }
                        }, 5000);
                    }
                }
            });

            // Handle stream close
            this.eventSource.addEventListener('close', (e) => {
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
            // Prevent displaying the same error twice
            if (this.displayedErrors[error.id]) {
                return;
            }
            this.displayedErrors[error.id] = true;

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
                10000,
                () => {
                    this.openModal(error);
                }
            );
        },

        jsonToMarkdownTable(jsonArray) {
            if (!Array.isArray(jsonArray)) return '';

            const keys = Object.keys(jsonArray[0]);
            const header = `| ${keys.join(' | ')} |`;
            const separator = `|${keys.map(() => ' --- |').join('')}`;
            const rows = jsonArray.map(obj =>
                `| ${keys.map(k => obj[k]).join(' | ')} |`
            );

            return [header, separator, ...rows].join('\n');
        },

        openModal(error) {
            const modal = document.getElementById('log-notifier-modal');
            if (!modal) return;

            const levelBadge = document.getElementById('modal-level');
            const levelClass = `log-notifier-level-${error.level}`;
            levelBadge.textContent = (error.level || 'unknown').toUpperCase();
            levelBadge.className = `log-notifier-level-badge ${levelClass}`;

            // Display message as main error message
            const messageElement = document.getElementById('modal-message');
            messageElement.textContent = error.message || 'N/A';

            // Display file and line
            document.getElementById('modal-file').textContent = error.file || 'N/A';
            document.getElementById('modal-line').textContent = error.line || 'N/A';
            document.getElementById('modal-time').textContent = error.occurred_at || 'N/A';

            // Format trace with line numbers
            const traceElement = document.getElementById('modal-trace');
            const trace = error.trace || 'No trace available';
            // const lines = trace.split('\n');
            function objectToMarkdown(obj, level = 2) {
                let md = '';
                for (const key in obj) {
                    const value = obj[key];
                    if (Array.isArray(value)) {
                        md += `${'#'.repeat(level)} ${key}\n`;
                        value.forEach((item, index) => {
                            if (typeof item === 'object') {
                                md += objectToMarkdown(item, level + 1);
                            } else {
                                md += `- ${item}\n`;
                            }
                        });
                    } else if (typeof value === 'object') {
                        md += `${'#'.repeat(level)} ${key}\n`;
                        md += objectToMarkdown(value, level + 1);
                    } else {
                        md += `- **${key}**: ${value}\n`;
                    }
                }
                return md;
            }

            const markdownText = objectToMarkdown(trace);

            traceElement.innerHTML = marked.parse(markdownText);

            // Show modal
            modal.classList.add('show');

            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });

            // Close on ESC key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    this.closeModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        },

        closeModal() {
            const modal = document.getElementById('log-notifier-modal');
            if (modal) {
                modal.classList.remove('show');
            }
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
