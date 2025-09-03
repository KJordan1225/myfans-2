<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\CreatorSubscribePageController;
use App\Http\Controllers\ConnectSubscriptionCheckoutController;
use App\Http\Controllers\PlanController;

Route::get('/', function () {
    return view('welcome'); 
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('user-profile', UserProfileController::class);
    
    Route::get('/@{username}', [UserProfileController::class, 'showByUsername'])
    ->where('username', '[A-Za-z0-9_-]+')
    ->name('profile.public');
    
    Route::get('/post-detail/{post}', [UserProfileController::class, 'showByUsernamePostDetail'])
        ->name('profile.public.post-detail');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/creator/{creator}/subscribe/checkout', [ConnectSubscriptionCheckoutController::class, 'start'])
        ->whereNumber('creator')
        ->name('creator.subscribe.checkout');
});

Route::get('/creators/{creator}/subscribe', [CreatorSubscribePageController::class, 'show'])
    ->middleware(['auth'])
    ->name('creator.subscribe.page');

Route::middleware('auth')->post('/plans', [PlanController::class, 'store'])
    ->name('plans.store');

require __DIR__.'/auth.php';
