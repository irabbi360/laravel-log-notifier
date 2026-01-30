# Laravel Log Notifier 🚨

[![Latest Version on Packagist](https://img.shields.io/packagist/v/irabbi360/laravel-log-notifier.svg?style=flat-square)](https://packagist.org/packages/irabbi360/laravel-log-notifier)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/irabbi360/laravel-log-notifier/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/irabbi360/laravel-log-notifier/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/irabbi360/laravel-log-notifier/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/irabbi360/laravel-log-notifier/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/irabbi360/laravel-log-notifier.svg?style=flat-square)](https://packagist.org/packages/irabbi360/laravel-log-notifier)

**Real-time Laravel error monitoring with Web Push Notifications**

Laravel Log Notifier is a developer-friendly Laravel package that monitors your application logs and sends **real-time web-based push notifications** whenever an error or critical issue is detected.

It helps you stay instantly informed about production issues without constantly checking log files or using expensive third-party services like Sentry or Bugsnag.

## ✨ Features

- 🔍 **Automatic Log Monitoring** - Scans Laravel log files for errors
- 🚨 **Real-time Alerts** - Detects error, critical, alert & emergency logs
- 🌐 **Web Push Notifications** - Browser-based notifications (no app required)
- 🖱️ **Click to View** - Navigate directly to error details from notifications
- ⚙️ **Fully Configurable** - Customize via `config/log-notifier.php`
- 🗂️ **Error Dashboard** - Beautiful UI to browse and manage errors
- 🧠 **Deduplication** - Groups repeated errors automatically
- 🔐 **Authentication** - Integrates with Laravel auth system
- 🚀 **Queue Support** - Async notification dispatching
- 🔒 **Security** - Masks sensitive data in logs

## 📦 Installation

Install the package via Composer:

```bash
composer require irabbi360/laravel-log-notifier
```

Run the install command:

```bash
php artisan log-notifier:install
```

Or manually publish and run migrations:

```bash
php artisan vendor:publish --tag="log-notifier-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="log-notifier-config"
```

Publish assets (service worker):

```bash
php artisan vendor:publish --tag="log-notifier-assets"
```

## ⚙️ Configuration

The config file `config/log-notifier.php` contains all settings:

```php
return [
    // Enable/disable the notifier
    'enabled' => env('LOG_NOTIFIER_ENABLED', true),

    // Path to Laravel log file
    'log_path' => storage_path('logs/laravel.log'),

    // Log levels to monitor
    'levels' => ['emergency', 'alert', 'critical', 'error'],

    // Check interval (seconds)
    'check_interval' => 10,

    // Group duplicate errors
    'deduplicate' => true,

    // Dashboard URL
    'dashboard_route' => '/log-notifier',

    // Middleware for dashboard
    'middleware' => ['web'],
    'auth_middleware' => ['auth'],

    // VAPID keys for Web Push
    'vapid' => [
        'public_key' => env('LOG_NOTIFIER_VAPID_PUBLIC_KEY'),
        'private_key' => env('LOG_NOTIFIER_VAPID_PRIVATE_KEY'),
    ],
];
```

## 🔑 Generate VAPID Keys

Web Push requires VAPID keys for authentication. Generate them with:

```bash
php artisan log-notifier:generate-vapid
```

Add the keys to your `.env` file:

```env
LOG_NOTIFIER_VAPID_PUBLIC_KEY=your-public-key
LOG_NOTIFIER_VAPID_PRIVATE_KEY=your-private-key
```

## 🚀 Usage

### Dashboard

Access the error dashboard at:

```
https://your-app.com/log-notifier
```

### Watch for Errors

Run the log watcher command:

```bash
# Watch continuously
php artisan log-notifier:watch

# Run once
php artisan log-notifier:watch --once
```

### Schedule the Watcher

Add to your `app/Console/Kernel.php`:

```php
$schedule->command('log-notifier:watch --once')->everyMinute();
```

Or in Laravel 11+ `routes/console.php`:

```php
Schedule::command('log-notifier:watch --once')->everyMinute();
```

### Send Test Notification

```bash
php artisan log-notifier:test
```

### Clear Old Errors

```bash
# Clear based on retention policy
php artisan log-notifier:clear

# Clear all errors
php artisan log-notifier:clear --all
```

### Using the Facade

```php
use Irabbi360\LaravelLogNotifier\Facades\LaravelLogNotifier;

// Watch for new errors
$errors = LaravelLogNotifier::watch();

// Get all errors
$errors = LaravelLogNotifier::getErrors(['level' => 'error']);

// Get statistics
$stats = LaravelLogNotifier::getStatistics(7); // Last 7 days

// Resolve an error
LaravelLogNotifier::resolve($errorId, auth()->id(), 'Fixed in commit abc123');

// Send test notification
LaravelLogNotifier::sendTestNotification();
```

## 🎨 Dashboard Features

- **Error List** - Browse all captured errors with pagination
- **Search & Filter** - Filter by level, status, date range
- **Error Details** - View full message, stack trace, context
- **Bulk Actions** - Resolve or delete multiple errors
- **Statistics** - Error counts by level and time period
- **Push Subscription** - Enable/disable browser notifications

## 📱 Web Push Notifications

1. Visit the dashboard
2. Click "Enable Notifications" button
3. Accept the browser permission prompt
4. You'll receive notifications when errors occur

### Notification Actions

- **View Details** - Opens error in dashboard
- **Mark Resolved** - Resolves error directly

## 🔧 Available Commands

| Command | Description |
|---------|-------------|
| `log-notifier:watch` | Watch logs for errors |
| `log-notifier:clear` | Clear stored errors |
| `log-notifier:test` | Send test notification |
| `log-notifier:generate-vapid` | Generate VAPID keys |

## 🏗️ Architecture

```
Laravel Logs
     ↓
LogWatcher (Command/Job)
     ↓
ErrorParser (Extracts error data)
     ↓
ErrorRepository (Stores/deduplicates)
     ↓
PushNotifier (Sends notifications)
     ↓
Service Worker → Browser Notification
     ↓
Click → Error Dashboard
```

## 🔒 Security

- **Sensitive Data Masking** - Passwords, API keys are automatically redacted
- **Dashboard Authentication** - Protected by your auth middleware
- **VAPID Authentication** - Secure push notification delivery
- **Rate Limiting** - Prevents notification flooding

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔐 Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## 👏 Credits

- [Fazle Rabbi](https://github.com/irabbi360)
- [All Contributors](../../contributors)

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
