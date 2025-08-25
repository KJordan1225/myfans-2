<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Show purchase page for a specific creator 
	// (route model binds User $creator)
    Route::get('/purchase/{creator}', [PurchaseController::class, 'show'])
        ->whereNumber('creator')
        ->name('purchase.show');

    // Create a PaymentIntent for this purchase
    Route::post('/purchase/intent', [PurchaseController::class, 'createIntent'])
        ->name('purchase.intent');
});