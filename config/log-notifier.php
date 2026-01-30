<?php

// config for Irabbi360/LaravelLogNotifier
return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Log Notifier
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable the log notifier functionality.
    |
    */
    'enabled' => env('LOG_NOTIFIER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, Log Notifier will log debug information about captured errors.
    | Useful for troubleshooting if listeners aren't firing.
    |
    */
    'debug' => env('LOG_NOTIFIER_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Log File Path
    |--------------------------------------------------------------------------
    |
    | The path to the Laravel log file that should be monitored.
    | Can be a single file path or a directory to scan all .log files.
    |
    | Examples:
    | - Single file: storage_path('logs/laravel.log')
    | - All logs in directory: storage_path('logs')
    |
    */
    'log_path' => storage_path('logs'),

    /*
    |--------------------------------------------------------------------------
    | Scan All Log Files
    |--------------------------------------------------------------------------
    |
    | When enabled, all .log files in the log directory will be monitored.
    | This is useful for multi-channel or daily rotated logs.
    |
    | When disabled, only the exact file path specified in 'log_path' is monitored.
    |
    */
    'scan_all_logs' => env('LOG_NOTIFIER_SCAN_ALL_LOGS', true),

    /*
    |--------------------------------------------------------------------------
    | Log Levels to Monitor
    |--------------------------------------------------------------------------
    |
    | Specify which log levels should trigger notifications.
    | Available levels: emergency, alert, critical, error, warning, notice, info, debug
    |
    */
    'levels' => [
        'emergency',
        'alert',
        'critical',
        'error',
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-Time Event Listener
    |--------------------------------------------------------------------------
    |
    | When enabled, errors are captured in real-time as they're logged
    | via Laravel's LogWritten event. This is the fastest way to detect errors.
    |
    | Disable this if you want to rely only on the log watcher command/scheduler.
    |
    */
    'use_event_listener' => env('LOG_NOTIFIER_USE_EVENT_LISTENER', true),

    /*
    |--------------------------------------------------------------------------
    | Check Interval
    |--------------------------------------------------------------------------
    |
    | How often (in seconds) the log watcher should check for new errors.
    |
    */
    'check_interval' => 10,

    /*
    |--------------------------------------------------------------------------
    | Deduplication
    |--------------------------------------------------------------------------
    |
    | When enabled, duplicate errors (same message, file, and line) will be
    | grouped together instead of creating separate entries.
    |
    */
    'deduplicate' => true,

    /*
    |--------------------------------------------------------------------------
    | Deduplication Time Window
    |--------------------------------------------------------------------------
    |
    | Time window (in minutes) for deduplication. Errors within this window
    | will be considered duplicates if they match.
    |
    */
    'deduplicate_window' => 60,

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure the web push notification appearance.
    |
    */
    'notification' => [
        'title' => 'Laravel Error 🚨',
        'icon' => '/vendor/log-notifier/icon.png',
        'badge' => '/vendor/log-notifier/badge.png',
        'vibrate' => [100, 50, 100],
        'require_interaction' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route
    |--------------------------------------------------------------------------
    |
    | The URL path where the error dashboard will be accessible.
    |
    */
    'dashboard_route' => '/log-notifier',

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the dashboard routes for protection.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Auth Middleware
    |--------------------------------------------------------------------------
    |
    | Additional authentication middleware for dashboard access.
    | Set to null or empty array to disable authentication.
    |
    */
    'auth_middleware' => ['auth'],

    /*
    |--------------------------------------------------------------------------
    | Error Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep error records in the database.
    | Set to 0 to keep errors indefinitely.
    |
    */
    'retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Mask Sensitive Data
    |--------------------------------------------------------------------------
    |
    | Patterns to mask in error messages and stack traces.
    | Useful for hiding passwords, API keys, etc.
    |
    */
    'mask_patterns' => [
        '/password["\']?\s*[=:]\s*["\']?[^"\'&\s]+/i',
        '/api[_-]?key["\']?\s*[=:]\s*["\']?[^"\'&\s]+/i',
        '/secret["\']?\s*[=:]\s*["\']?[^"\'&\s]+/i',
        '/token["\']?\s*[=:]\s*["\']?[^"\'&\s]+/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mask Replacement
    |--------------------------------------------------------------------------
    |
    | The string to use when masking sensitive data.
    |
    */
    'mask_replacement' => '[REDACTED]',

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limit the number of notifications sent within a time period.
    |
    */
    'rate_limit' => [
        'enabled' => true,
        'max_notifications' => 10,
        'per_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by the package.
    |
    */
    'tables' => [
        'errors' => 'log_notifier_errors',
    ],

];
