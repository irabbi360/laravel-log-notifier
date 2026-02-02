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
    | Log Levels to Monitor
    |--------------------------------------------------------------------------
    |
    | Specify which log levels should trigger notifications.
    | Available levels: emergency, alert, critical, error
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
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure the toast notification appearance.
    |
    */
    'notification' => [
        'title' => 'Laravel Error 🚨',
        'icon' => '/vendor/log-notifier/icon.png',
        'sound' => true, // Play sound on critical errors
    ],

];
