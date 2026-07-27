<?php

use App\Http\Controllers\AppliedJobController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CalendarCountsController;
use App\Http\Controllers\CompanyPreferenceController;
use App\Http\Controllers\CustomJobController;
use App\Http\Controllers\ExcludedTermController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingDetailsController;
use App\Http\Controllers\JobListingNoteController;
use App\Http\Controllers\JobListingStatusController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\KanbanStatusController;
use App\Http\Controllers\LearningTopicController;
use App\Http\Controllers\RunScrapeSchedulesController;
use App\Http\Controllers\ScrapeScheduleController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::get('/jobs', HomeController::class)->name('jobs');
Route::view('/jobs/calendar', 'calendar')->name('calendar');
Route::get('/jobs/calendar/counts', CalendarCountsController::class)->name('calendar.counts');
Route::get('/jobs/kanban', KanbanController::class)->name('kanban');
Route::post('/jobs/custom', CustomJobController::class)->name('jobs.custom.store');
Route::post('/jobs/{jobListing}/kanban-status', KanbanStatusController::class)->name('jobs.kanban-status');
Route::view('/bible', 'coming-soon', ['pageTitle' => 'Bible'])->name('bible');
Route::get('/learning', [LearningTopicController::class, 'index'])->name('learning');
Route::post('/learning/topics', [LearningTopicController::class, 'store'])->name('learning-topics.store');
Route::get('/learning/topics/{learningTopic}/edit', [LearningTopicController::class, 'edit'])->name('learning-topics.edit');
Route::put('/learning/topics/{learningTopic}', [LearningTopicController::class, 'update'])->name('learning-topics.update');
Route::delete('/learning/topics/{learningTopic}', [LearningTopicController::class, 'destroy'])->name('learning-topics.destroy');
Route::redirect('/settings', '/settings/backup')->name('settings');
Route::get('/settings/backup', [BackupController::class, 'index'])->name('settings.backup');
Route::post('/settings/backup', [BackupController::class, 'store'])->name('settings.backup.store');
Route::delete('/settings/backup', [BackupController::class, 'destroy'])->name('settings.backup.destroy');
Route::view('/settings/restore', 'settings.restore')->name('settings.restore');
Route::post('/jobs/{jobListing}/status', JobListingStatusController::class)->name('jobs.status');
Route::put('/jobs/{jobListing}/details', JobListingDetailsController::class)->name('jobs.details.update');
Route::post('/jobs/{jobListing}/notes', [JobListingNoteController::class, 'store'])->name('jobs.notes.store');
Route::delete('/jobs/{jobListing}/notes/{note}', [JobListingNoteController::class, 'destroy'])->name('jobs.notes.destroy');
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
Route::get('/jobs/cron/{scrapeSchedule}/edit', [ScrapeScheduleController::class, 'edit'])->name('cron.edit');
Route::put('/jobs/cron/{scrapeSchedule}', [ScrapeScheduleController::class, 'update'])->name('cron.update');
Route::delete('/jobs/cron/{scrapeSchedule}', [ScrapeScheduleController::class, 'destroy'])->name('cron.destroy');
Route::post('/jobs/cron/{scrapeSchedule}/run', [ScrapeScheduleController::class, 'run'])->name('cron.schedule.run');
Route::get('/jobs/cron/run', RunScrapeSchedulesController::class)->name('cron.run');
Route::get('/cron/run', RunScrapeSchedulesController::class)->name('cron.run.legacy');
Route::get('/jobs/applied-to', AppliedJobController::class)->name('applied-to');
