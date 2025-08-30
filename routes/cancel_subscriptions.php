<?php

use App\Http\Controllers\SubscriptionCancelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/subscriptions/{subscription}/cancel',
        [SubscriptionCancelController::class, 'cancelAtPeriodEnd']
    )->name('subscriptions.cancel');

    Route::post('/subscriptions/{subscription}/cancel-now',
        [SubscriptionCancelController::class, 'cancelNow']
    )->name('subscriptions.cancel-now');
});
