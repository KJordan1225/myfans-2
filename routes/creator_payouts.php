<?php

use App\Http\Controllers\CreatorPayoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('creator')->middleware(['auth', 'role:creator'])->group(function () {
    Route::get('/settings/payouts',          [CreatorPayoutController::class, 'index'])->name('creator.payouts.index');
    Route::post('/settings/payouts/connect', [CreatorPayoutController::class, 'connect'])->name('creator.payouts.connect');
    Route::get('/settings/payouts/return',   [CreatorPayoutController::class, 'return'])->name('creator.payouts.return');
    Route::get('/settings/payouts/refresh',  [CreatorPayoutController::class, 'refresh'])->name('creator.payouts.refresh');
    Route::get('/settings/payouts/dashboard',[CreatorPayoutController::class, 'dashboard'])->name('creator.payouts.dashboard');
    Route::post('/settings/payouts/disconnect',[CreatorPayoutController::class, 'disconnect'])->name('creator.payouts.disconnect');
});
