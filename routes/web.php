<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {    
    Route::get('/user-profile/{id}/edit', [UserProfileController::class, 'edit'])->name('user-profile.edit');    
    Route::put('/user-profile/{id}/update', [UserProfileController::class, 'update'])->name('user-profile.update');    
});

require __DIR__.'/auth.php';
