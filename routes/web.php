<?php

use App\Models\User;
use App\Models\CreatorPlan;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ConnectOnboardingController;
use App\Http\Controllers\CreatorSubscribePageController;
use App\Http\Controllers\ConnectSubscriptionCheckoutController;
use App\Http\Controllers\SubscribeByNameController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\CreatorPlanController;
use App\Http\Controllers\PayPalWebhookController;



Route::get('/', function () {
    return view('welcome'); 
});

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

Route::get('/subscribe/{user:name}', [SubscribeByNameController::class, 'callCreatorSubscribe'])
    ->name('subscribe.byUsername');
    
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

// ---------- Public-ish: show a creator profile + plans ----------
Route::get('/@{username}', [PostController::class, 'showPosts'])
    ->name('posts.username');

// ---------- PAYPAL SUBSCRIPTION ROUTES - BEGIN ---------------------------
Route::middleware('auth')->group(function () {
  Route::get('/@{username}', [PayPalController::class,'showCreator'])->name('creator.page');

  // Subscribe flow
  Route::get('/subscribe/{plan:paypal_plan_id}', [PayPalController::class,'show'])->name('paypal.subscribe.show');
  Route::post('/paypal/verify',  [PayPalController::class,'verify'])->name('paypal.subscribe.verify');
  Route::post('/paypal/cancel',  [PayPalController::class,'cancel'])->name('paypal.subscribe.cancel');
  Route::post('/paypal/switch',  [PayPalController::class,'switchPlan'])->name('paypal.subscribe.switch'); // optional

  // Creator plans
  Route::get('/creator/plans',  [CreatorPlanController::class,'index'])->name('creator.plans.index');
  Route::post('/creator/plans', [CreatorPlanController::class,'store'])->name('creator.plans.store');
  Route::get('/creator/plans/{plan}/edit',[CreatorPlanController::class, 'edit'])->name('creator.plans.edit');
  Route::put('/creator/plans/{plan}',     [CreatorPlanController::class, 'update'])->name('creator.plans.update');

});

Route::post('/webhooks/paypal', [PayPalWebhookController::class,'handle'])
  ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
  ->name('webhooks.paypal');





require __DIR__.'/auth.php';
