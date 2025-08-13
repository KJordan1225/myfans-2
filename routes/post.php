<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\CreatorController;

Route::prefix('creator')->middleware(['auth'])->group(function () {
    Route::get('/posts/list', [PostController::class, 'authUserPostsList'])->name('creator.posts.list');
    Route::get('/posts/create', [PostController::class, 'authUserPostsCreate'])->name('creator.posts.create');
});

// Route::middleware(['auth'])->group(function () {
    
// });