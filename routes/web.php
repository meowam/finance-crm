<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::post('/lead-request', [LandingController::class, 'store'])->name('landing.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/notifications/{notification}/open-policy', [NotificationRedirectController::class, 'openPolicy'])
        ->name('notifications.open-policy');

    Route::post('/notifications/{notification}/process', [NotificationRedirectController::class, 'process'])
        ->name('notifications.process');
});