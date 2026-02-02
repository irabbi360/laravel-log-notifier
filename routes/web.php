<?php

use Illuminate\Support\Facades\Route;
use Irabbi360\LaravelLogNotifier\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Log Notifier Web Routes
|--------------------------------------------------------------------------
|
| Routes for real-time error streaming via Server-Sent Events (SSE).
| The stream endpoint delivers errors to toast notifications in the browser.
|
*/

Route::get('/api/stream', [DashboardController::class, 'stream'])
    ->name('log-notifier.api.stream')
    ->middleware(['web']);
