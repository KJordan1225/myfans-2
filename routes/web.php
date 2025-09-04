<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\CreatorSubscribePageController;
use App\Http\Controllers\ConnectSubscriptionCheckoutController;
use App\Http\Controllers\PlanController;
use App\Models\User;
use App\Models\CreatorPlan;


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

Route::middleware('auth')->group(function () {
    // Creator Monetize page
    Route::view('/dashboard/monetize', 'connect.status')->name('creator.monetize');

    // Creator Plan form page
    Route::get('/dashboard/plans', function () {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $plans = CreatorPlan::query()->where('creator_id', $user->id)->latest()->get();
        return view('plans.create', compact('plans', 'user'));
    })->name('plans.create.form');

    // Subscriber: My Subscriptions page
    Route::view('/subscriptions', 'subscriptions.index')->name('subscriptions.index');
});

// Public creator page (list plans with subscribe buttons)
Route::get('/@{creator}', function (User $creator) {
    $plans = CreatorPlan::query()->where('creator_id', $creator->id)->where('active', true)->orderBy('amount')->get();
    abort_if($plans->isEmpty(), 404);
    return view('creators.show', compact('creator', 'plans'));
})->name('creators.show');


require __DIR__.'/auth.php';
