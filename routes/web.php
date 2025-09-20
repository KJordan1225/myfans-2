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
use App\Http\Controllers\StripeWebhookController;



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

// ====================STRIPE SUBSCRIPTION ROUTES ==================
Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');


require __DIR__.'/auth.php';
