<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

Route::middleware('auth')->group(function () {
    Route::post('/plans/{plan}/subscribe', [CheckoutController::class, 'subscribe'])->name('plans.subscribe');
    Route::get('/subscribe/success', [CheckoutController::class, 'success'])->name('subscribe.success');
    Route::get('/subscribe/cancel',  [CheckoutController::class, 'cancel'])->name('subscribe.cancel');
});
