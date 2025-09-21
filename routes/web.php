<?php

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ConnectOnboardingController;
use App\Http\Controllers\CreatorSubscribePageController;
use App\Http\Controllers\SubscribeByNameController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CreatorPlanController;
use App\Http\Controllers\SubscribeController;



Route::get('/', function () {
    return view('welcome'); 
});

Route::get('/subscribe/success', [SubscribeController::class, 'success'])
    ->name('subscribe.success');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('user-profile', UserProfileController::class);
    
    Route::get('/post-detail/{post}', [UserProfileController::class, 'showByUsernamePostDetail'])
        ->name('profile.public.post-detail');
});

// =========== SHOW POSTS ===============
Route::get('/view/{username}', [PostController::class, 'showPosts'])
    ->name('posts.show');


/**
 * Spatie webhook endpoint (two secrets via {configKey})
 *  - /stripe/webhook/account  (platform events)
 *  - /stripe/webhook/connect  (connected accounts events)
 */
Route::stripeWebhooks('stripe/webhook/{configKey}');

// ---- Creator-facing pages/actions ----
Route::middleware(['auth', 'verified'])->group(function () {
    // Simple “Monetize” page that shows current Stripe status + actions (view below)
    Route::view('/dashboard/monetize', 'connect.status')
        ->name('creator.monetize');

    // Actions that hit your existing controller (from your earlier code)
    Route::get('/connect/{creator}/start',     [ConnectOnboardingController::class, 'start'])
        ->name('connect.start');
    Route::get('/connect/{creator}/return',    [ConnectOnboardingController::class, 'return'])
        ->name('connect.return');
    Route::post('/connect/{creator}/dashboard',[ConnectOnboardingController::class, 'dashboard'])
        ->name('connect.dashboard');
    Route::post('/connect/{creator}/status',   [ConnectOnboardingController::class, 'status'])
        ->name('connect.status');

    // (Optional convenience routes for “current user” so you don’t need the {creator} param)
    Route::get('/me/connect/start', function () {
        return redirect()->route('connect.start', auth()->id());
    })->name('connect.start.me');

    Route::get('/me/connect/return', function () {
        return redirect()->route('connect.return', auth()->id());
    })->name('connect.return.me');

    Route::post('/me/connect/dashboard', function () {
        return redirect()->route('connect.dashboard', auth()->id());
    })->name('connect.dashboard.me');

    Route::post('/me/connect/status', function () {
        return redirect()->route('connect.status', auth()->id());
    })->name('connect.status.me');
});

// ---- Dev/Admin: view recent webhook calls (guard as you like) ----
Route::middleware(['auth'])->group(function () {
    Route::get('/dev/webhooks', function () {
        $calls = \Spatie\WebhookClient\Models\WebhookCall::latest()->limit(25)->get();
        return view('dev.webhooks', compact('calls'));
    })->name('dev.webhooks');
});
// ===== END Creator-facing pages/actions =====


// ---- Creator Plans (CRUD) ----
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/creator/plans',            [CreatorPlanController::class, 'index'])->name('creator.plans.index');
    Route::get('/creator/plans/create',     [CreatorPlanController::class, 'create'])->name('creator.plans.create');
    Route::post('/creator/plans',           [CreatorPlanController::class, 'store'])->name('creator.plans.store');
    Route::get('/creator/plans/{plan}/edit',[CreatorPlanController::class, 'edit'])->name('creator.plans.edit');
    Route::put('/creator/plans/{plan}',     [CreatorPlanController::class, 'update'])->name('creator.plans.update');
    Route::delete('/creator/plans/{plan}',  [CreatorPlanController::class, 'destroy'])->name('creator.plans.destroy');
});
// ===== END Creator Plans (CRUD) =====

// ---- Fan Subscriptions ----
Route::middleware(['auth','verified'])->group(function () {
    // Browse creator's plans
    Route::get('/subscribe/{creator}', [SubscribeController::class, 'showPlans'])
        ->name('subscribe.show');

    // Start checkout for a specific plan
    Route::post('/subscribe/start/{plan}', [SubscribeController::class, 'start'])->name('subscribe.start');

    // Success/cancel returns  
          
    Route::get('/subscribe/cancelled/{creator}', [SubscribeController::class, 'cancelled'])
        ->name('subscribe.cancelled');


    // Manage my subscriptions
    Route::get('/subscriptions/mine', [SubscribeController::class, 'mine'])->name('subscriptions.mine');
    Route::post('/subscriptions/{subscription}/cancel', [SubscribeController::class, 'cancel'])->name('subscriptions.cancel');
});
// ===== END Fan Subscriptions =====


// ---------- Public-ish: show a creator profile + plans ----------
Route::get('/@{username}', [PostController::class, 'showPosts'])
    ->name('posts.username');


require __DIR__.'/auth.php';
