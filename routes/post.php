<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\CreatorController;

Route::prefix('creator')->middleware(['auth', 'role:creator'])->group(function () {
    Route::get('/posts/list', [PostController::class, 'authUserPostsList'])->name('creator.posts.list');
    Route::get('/posts/create', [PostController::class, 'authUserPostsCreate'])->name('creator.posts.create'); 
    Route::post('/posts/store', [PostController::class, 'authUserPostsStore'])->name('creator.posts.store');
    Route::get('/posts/{id}/edit', [PostController::class, 'authUserPostsEdit'])->name('creator.posts.edit');
    Route::put('/posts/{post}/update', [PostController::class, 'authUserPostsUpdate'])->name('creator.posts.update');
});




