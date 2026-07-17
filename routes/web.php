<?php

use App\Http\Controllers\AppliedJobController;
use App\Http\Controllers\CompanyPreferenceController;
use App\Http\Controllers\ExcludedTermController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingStatusController;
use App\Http\Controllers\RunScrapeSchedulesController;
use App\Http\Controllers\ScrapeScheduleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/jobs', HomeController::class)->name('jobs');
Route::view('/bible', 'coming-soon', ['pageTitle' => 'Bible'])->name('bible');
Route::view('/learning', 'coming-soon', ['pageTitle' => 'Learning'])->name('learning');
Route::view('/settings', 'coming-soon', ['pageTitle' => 'Settings'])->name('settings');
Route::post('/jobs/{jobListing}/status', JobListingStatusController::class)->name('jobs.status');
Route::post('/jobs/{jobListing}/company-preference', [CompanyPreferenceController::class, 'store'])
    ->name('jobs.company-preference');
Route::get('/jobs/company-preferences', [CompanyPreferenceController::class, 'index'])->name('company-preferences.index');
Route::delete('/jobs/company-preferences/{companyPreference}', [CompanyPreferenceController::class, 'destroy'])
    ->name('company-preferences.destroy');
Route::resource('jobs/excluded-terms', ExcludedTermController::class)
    ->names('excluded-terms')
    ->except('show');
Route::get('/jobs/cron', [ScrapeScheduleController::class, 'index'])->name('cron');
Route::post('/jobs/cron', [ScrapeScheduleController::class, 'store'])->name('cron.store');
Route::get('/jobs/cron/run', RunScrapeSchedulesController::class)->name('cron.run');
Route::get('/jobs/applied-to', AppliedJobController::class)->name('applied-to');
