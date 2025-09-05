<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionController;


Route::middleware(['auth'])->group(function () {
    // Subscriber-facing   
    Route::post('/subscriptions/{subscription}/subscribe', [SubscriptionController::class, 'subscribe'])
        ->name('subscriptions.subscribe');
    Route::post('/subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])
        ->name('subscriptions.resume');
});

Route::middleware(['auth', 'role:creator'])->group(function () {
    // Creator-facing (exactly one subscription per creator)
    Route::get('/creator/subscription', [SubscriptionController::class, 'creatorShow'])
        ->name('creator.subscription.show');
    Route::post('/creator/subscription', [SubscriptionController::class, 'creatorStoreOrUpdate'])
        ->name('creator.subscription.store'); // create or update one plan
    Route::get('/creator/subscription/subscribers', [SubscriptionController::class, 'creatorSubscribers'])
        ->name('creator.subscription.subscribers'); // list subscribers to my plan
});
