<?php

use App\Http\Controllers\AppliedJobController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\JobListingStatusController;
use App\Http\Controllers\RunScrapeSchedulesController;
use App\Http\Controllers\ScrapeScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/jobs/{jobListing}/status', JobListingStatusController::class)->name('jobs.status');
Route::get('/cron', [ScrapeScheduleController::class, 'index'])->name('cron');
Route::post('/cron', [ScrapeScheduleController::class, 'store'])->name('cron.store');
Route::get('/cron/run', RunScrapeSchedulesController::class)->name('cron.run');
Route::get('/applied-to', AppliedJobController::class)->name('applied-to');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('job-listings', JobListingController::class)->only(['index', 'show']);
});

require __DIR__.'/settings.php';
