<?php

use App\Http\Controllers\NotificationRedirectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/{notification}/open-policy', [NotificationRedirectController::class, 'openPolicy'])
        ->name('notifications.open-policy');

    Route::post('/notifications/{notification}/process', [NotificationRedirectController::class, 'process'])
        ->name('notifications.process');
});