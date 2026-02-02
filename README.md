# Laravel Log Notifier

[![Latest Version on Packagist](https://img.shields.io/packagist/v/irabbi360/laravel-log-notifier.svg?style=flat-square)](https://packagist.org/packages/irabbi360/laravel-log-notifier)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/irabbi360/laravel-log-notifier/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/irabbi360/laravel-log-notifier/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/irabbi360/laravel-log-notifier/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/irabbi360/laravel-log-notifier/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/irabbi360/laravel-log-notifier.svg?style=flat-square)](https://packagist.org/packages/irabbi360/laravel-log-notifier)

**Real-time Laravel error monitoring with Toast Notifications**

Laravel Log Notifier is a developer-friendly Laravel package that captures application exceptions and sends **real-time in-app toast notifications** instantly.

It helps you stay informed about errors as they happen, without constantly checking logs or using expensive third-party services like Sentry or Bugsnag.

## Key Features

- **Real-time Exception Capture** - Instantly detects and captures exceptions
- **Toast Notifications** - In-app alerts appear immediately in top-right corner
- **Global Display** - Works across your entire application, no permissions needed
- **Interactive Modal** - Click notifications to view full error details
- **Smart Pause** - Hover over notifications to pause auto-close timer
- **Sound Alerts** - Optional beep for critical errors
- **Professional UI** - Styled like Laravel's native error page
- **Error Details** - Shows message, file, line number, and full stack trace
- **Lightweight** - No database required, uses simple JSON storage
- **Secure** - Built with Laravel auth in mind
- **Zero Setup** - Works out of the box, no complex configuration
- **Production Ready** - Efficient and reliable error handling

## Installation

Install via Composer:

```bash
composer require irabbi360/laravel-log-notifier
```

Publish the config file:

```bash
php artisan vendor:publish --tag="log-notifier-config"
```

## Configuration

Configure your settings in `config/log-notifier.php`:

```php
return [
    'enabled' => env('LOG_NOTIFIER_ENABLED', true),
    
    'levels' => ['error', 'critical', 'alert', 'emergency'],
    
    'notification' => [
        'title' => 'Laravel Error 🚨',
        'icon' => '/vendor/log-notifier/icon.png',
        'sound' => true, // Play sound on critical errors
    ],
];
```

## Quick Start

### Step 1: Register Exception Handler

In your `bootstrap/app.php`, add the Log Notifier exception handler:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Irabbi360\LaravelLogNotifier\LogNotifierWatcher;

return Application::configure(basePath: dirname(__DIR__))
    ->withConfiguration()
    ->withRouting()
    ->withMiddleware(function (Middleware $middleware) {
        // ... your middleware
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Register Log Notifier exception handler
        LogNotifierWatcher::handles($exceptions);
    })
    ->create();
```

### Step 2: Add Notification Component

Add the notification component to your main layout (e.g., `resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html>
<head>
    <!-- your head content -->
</head>
<body>
    <!-- your content -->

    <!-- Add Log Notifier notification component -->
    {!! LogNotifier::notification() !!}
</body>
</html>
```

### Step 3: Enable in Environment

Make sure the package is enabled (default is true):

```env
LOG_NOTIFIER_ENABLED=true
```

Done! All exceptions will now trigger real-time toast notifications. 🎉

## 📖 Usage

### Automatic Error Capture

The package automatically captures **all exceptions** in your Laravel application. No manual setup required:

```php
// Any unhandled exception will trigger a toast notification
throw new Exception('Something failed!');

// Database errors
DB::connection()->getPdo();

// Model validation errors
User::findOrFail($invalidId);

// Any other exception in your app...
```

### Enable/Disable Notifications

Control the package via configuration:

```php
// config/log-notifier.php
'enabled' => env('LOG_NOTIFIER_ENABLED', true),
```

Or via environment variable:

```env
LOG_NOTIFIER_ENABLED=true   # Enable notifications
LOG_NOTIFIER_ENABLED=false  # Disable notifications
```

### Filter Error Levels

Control which error levels trigger notifications:

```php
// config/log-notifier.php
'levels' => [
    'emergency',  // System is unusable
    'alert',      // Action must be taken immediately
    'critical',   // Critical condition
    'error',      // Error condition
    // 'warning', 'notice', 'info', 'debug' - not monitored by default
],
```

### Display Notifications in Your App

Add this **single line** to your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```blade
{!! LogNotifier::notification() !!}
```

That's it! Errors will now show as toast notifications across your entire application.

## Toast Notifications

Toast notifications appear automatically when exceptions occur in your application:

### Features

- **Real-time** - Instant notification delivery via Server-Sent Events (SSE)
- **Always Visible** - Fixed position in top-right corner
- **Color-Coded** - Visual indication by error level (red for error, orange for warning, etc.)
- **Hover to Pause** - Auto-close timer pauses when hovering over the notification
- **Click for Details** - Open modal with full error information
- **Sound Alerts** - Optional beep for critical/alert level errors
- **Professional Styling** - Matches Laravel's native error page design

### User Interactions

- **Hover** → Pause the auto-close countdown timer
- **Move Away** → Resume the countdown
- **Click Toast** → Open modal with detailed error information
- **Click Close Button (×)** → Dismiss notification immediately
- **Press ESC Key** → Close the error details modal
- **Click Outside Modal** → Close the error details modal

## How It Works

The package uses **Server-Sent Events (SSE)** for real-time error delivery:

```
Application Exception
        ↓
ExceptionTracker (JSON storage)
        ↓
SSE Stream (/api/stream)
        ↓
Browser EventSource Listener
        ↓
Deduplication Check
        ↓
Toast Notification Display
        ↓
User Interaction (Hover/Click)
```

### Real-Time Delivery

- Exceptions are captured instantly and streamed to the browser via SSE
- No polling or page reloads required
- Browser maintains persistent connection for instant notifications
- Automatic reconnection if connection drops

## Security

- **Built with Laravel Auth** - Works with your existing auth system
- **No External Services** - Everything runs on your server
- **No Sensitive Data Logging** - Exceptions are not persisted beyond current session

## Testing

```bash
composer test
```

## Troubleshooting

### Toast Notifications Not Appearing?

**Check 1: Verify package is enabled**
```env
LOG_NOTIFIER_ENABLED=true
```

**Check 2: Ensure notification view is included in layout**
```blade
{!! LogNotifier::notification() !!}
```

**Check 3: Check browser console for errors**
Open DevTools (F12) and look for JavaScript errors.

**Check 4: Verify error levels match configuration**
```php
// config/log-notifier.php
'levels' => ['emergency', 'alert', 'critical', 'error'],
```

### No Sound on Critical Errors?

Make sure sound is enabled in config:
```php
'notification' => [
    'sound' => true,
],
```

Note: Browsers may require user interaction before playing audio.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Fazle Rabbi](https://github.com/irabbi360)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
