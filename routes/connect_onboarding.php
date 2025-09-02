<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConnectOnboardingController;

Route::middleware('auth')->group(function () {
    Route::get('/connect/start',   [ConnectOnboardingController::class, 'start'])->name('connect.start');
    Route::get('/connect/refresh', [ConnectOnboardingController::class, 'refresh'])->name('connect.refresh');
    Route::get('/connect/return',  [ConnectOnboardingController::class, 'return'])->name('connect.return');
});
