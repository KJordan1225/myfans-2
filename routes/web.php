<?php

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ConnectOnboardingController;
use App\Http\Controllers\CreatorSubscribePageController;
use App\Http\Controllers\ConnectSubscriptionCheckoutController;


Route::get('/', function () {
    return view('welcome'); 
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('user-profile', UserProfileController::class);
    
    Route::get('/post-detail/{post}', [UserProfileController::class, 'showByUsernamePostDetail'])
        ->name('profile.public.post-detail');
});

// ---------- Public-ish: show a creator profile + plans ----------
Route::get('/@{username}', function (string $username) {
    /** @var \App\Models\User $creator */
    $creator = User::query()
        ->where('name', $username)
        ->with(['profile', 'creatorPlans' => function ($q) {
            $q->where('active', true)->orderBy('amount');
        }])
        ->firstOrFail();

    return view('creators.show', compact('creator'));
})->name('creators.show');

// ---------- Auth-only subscription actions ----------
Route::middleware('auth')->group(function () {

    // Subscribing via Stripe Checkout
    Route::post('/plans/{plan}/subscribe', [CheckoutController::class, 'subscribe'])
        ->name('plans.subscribe');

    // Post-checkout landing
    Route::get('/subscribe/success', [CheckoutController::class, 'success'])
        ->name('subscribe.success');
    Route::get('/subscribe/cancel',  [CheckoutController::class, 'cancel'])
        ->name('subscribe.cancel');

    // Optional: My subscriptions page (list & cancel)
    Route::view('/me/subscriptions', 'subscriptions.index')
        ->name('me.subscriptions');

    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');

    // Creator: create plans (already in your stack)
    Route::post('/plans', [PlanController::class, 'store'])
        ->name('plans.store');

    // Stripe Connect onboarding (already in your stack)
    Route::get('/connect/start',   [ConnectOnboardingController::class, 'start'])->name('connect.start');
    Route::get('/connect/refresh', [ConnectOnboardingController::class, 'refresh'])->name('connect.refresh');
    Route::get('/connect/return',  [ConnectOnboardingController::class, 'return'])->name('connect.return');
});

// Webhooks (with signature middleware)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handle'])
    ->middleware('stripe.signature')
    ->name('stripe.webhook');
// ========== END Auth-only subscription actions ==========


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
    Route::view('/dashboard/monetize', 'connect.status')
        ->name('creator.monetize');

    // Creator Plan form page
    Route::get('/dashboard/plans', [PlanController::class, 'index']) 
        ->name('plans.create.form');

    // Subscriber: My Subscriptions page
    Route::view('/subscriptions', 'subscriptions.index')->name('subscriptions.index');
});

require __DIR__.'/auth.php';
