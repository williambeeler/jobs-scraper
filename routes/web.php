<?php

use App\Http\Controllers\JobListingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('job-listings', JobListingController::class)->only(['index', 'show']);
});

require __DIR__.'/settings.php';
