<?php

use Illuminate\Support\Facades\Route;
use Irabbi360\LaravelLogNotifier\Http\Controllers\DashboardController;
use Irabbi360\LaravelLogNotifier\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Log Notifier Web Routes
|--------------------------------------------------------------------------
*/

$dashboardRoute = config('log-notifier.dashboard_route', '/log-notifier');
$middleware = array_merge(
    config('log-notifier.middleware', ['web']),
    config('log-notifier.auth_middleware', ['auth'])
);

Route::prefix($dashboardRoute)
    ->middleware($middleware)
    ->name('log-notifier.')
    ->group(function () {
        
        // Dashboard routes
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/errors/{id}', [DashboardController::class, 'show'])->name('errors.show');
        Route::post('/errors/{id}/resolve', [DashboardController::class, 'resolve'])->name('errors.resolve');
        Route::post('/errors/{id}/unresolve', [DashboardController::class, 'unresolve'])->name('errors.unresolve');
        Route::delete('/errors/{id}', [DashboardController::class, 'destroy'])->name('errors.destroy');
        Route::post('/errors/bulk', [DashboardController::class, 'bulkAction'])->name('errors.bulk');
        
        // API routes
        Route::get('/api/statistics', [DashboardController::class, 'statistics'])->name('api.statistics');
        
        // Notification routes
        Route::post('/subscribe', [NotificationController::class, 'subscribe'])->name('subscribe');
        Route::post('/unsubscribe', [NotificationController::class, 'unsubscribe'])->name('unsubscribe');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('preferences');
        Route::get('/vapid-public-key', [NotificationController::class, 'vapidPublicKey'])->name('vapid');
        Route::post('/subscription-status', [NotificationController::class, 'status'])->name('subscription.status');
    });
