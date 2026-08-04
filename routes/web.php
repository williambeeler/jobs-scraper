<?php

use App\Http\Controllers\AppliedJobController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CalendarCountsController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CompanyPreferenceController;
use App\Http\Controllers\CustomJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\ExcludedTermController;
use App\Http\Controllers\FlashCardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobListingDetailsController;
use App\Http\Controllers\JobListingNoteController;
use App\Http\Controllers\JobListingStatusController;
use App\Http\Controllers\JobSearchController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\KanbanStatusController;
use App\Http\Controllers\LearningModuleController;
use App\Http\Controllers\LearningTopicController;
use App\Http\Controllers\PortfolioProjectController;
use App\Http\Controllers\RunScrapeSchedulesController;
use App\Http\Controllers\ScrapeScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
Route::get('/jobs', HomeController::class)->name('jobs.index');
Route::view('/jobs/calendar', 'calendar')->name('jobs.calendar');
Route::get('/jobs/calendar/counts', CalendarCountsController::class)->name('jobs.calendar.counts');
Route::get('/jobs/kanban', KanbanController::class)->name('jobs.kanban');
Route::get('/jobs/search', JobSearchController::class)->name('jobs.search');
Route::post('/jobs/custom', CustomJobController::class)->name('jobs.custom.store');
Route::post('/jobs/{jobListing}/kanban-status', KanbanStatusController::class)->name('jobs.kanban-status');
Route::view('/bible', 'coming-soon', ['pageTitle' => 'Bible'])->name('bible');
Route::view('/learning', 'learning.dashboard')->name('learning');
Route::prefix('learning/agenda')->name('learning.agenda')->group(function (): void {
    Route::get('/', [LearningTopicController::class, 'index']);
    Route::post('/topics', [LearningTopicController::class, 'store'])->name('.topics.store');
    Route::get('/topics/{learningTopic}/edit', [LearningTopicController::class, 'edit'])->name('.topics.edit');
    Route::put('/topics/{learningTopic}', [LearningTopicController::class, 'update'])->name('.topics.update');
    Route::delete('/topics/{learningTopic}', [LearningTopicController::class, 'destroy'])->name('.topics.destroy');
});
Route::resource('learning/certificates', CertificateController::class)
    ->names('learning.certificates');
Route::resource('learning/projects', PortfolioProjectController::class)
    ->names('learning.projects');
Route::post('learning/certificates/{certificate}/modules', [LearningModuleController::class, 'storeForCertificate'])
    ->name('learning.certificates.modules.store');
Route::post('learning/projects/{portfolioProject}/modules', [LearningModuleController::class, 'storeForProject'])
    ->name('learning.projects.modules.store');
Route::get('learning/modules/{learningModule}/edit', [LearningModuleController::class, 'edit'])
    ->name('learning.modules.edit');
Route::put('learning/modules/{learningModule}', [LearningModuleController::class, 'update'])
    ->name('learning.modules.update');
Route::delete('learning/modules/{learningModule}', [LearningModuleController::class, 'destroy'])
    ->name('learning.modules.destroy');
Route::resource('learning/decks', DeckController::class)->names('learning.decks');
Route::post('learning/decks/{deck}/cards', [FlashCardController::class, 'store'])
    ->name('learning.decks.cards.store');
Route::get('learning/decks/{deck}/cards/create', [FlashCardController::class, 'create'])
    ->name('learning.decks.cards.create');
Route::get('learning/cards/{flashCard}', [FlashCardController::class, 'show'])
    ->name('learning.cards.show');
Route::get('learning/cards/{flashCard}/edit', [FlashCardController::class, 'edit'])
    ->name('learning.cards.edit');
Route::put('learning/cards/{flashCard}', [FlashCardController::class, 'update'])
    ->name('learning.cards.update');
Route::delete('learning/cards/{flashCard}', [FlashCardController::class, 'destroy'])
    ->name('learning.cards.destroy');
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
Route::get('/jobs/company-preferences', [CompanyPreferenceController::class, 'index'])->name('jobs.company-preferences.index');
Route::delete('/jobs/company-preferences/{companyPreference}', [CompanyPreferenceController::class, 'destroy'])
    ->name('jobs.company-preferences.destroy');
Route::resource('jobs/excluded-terms', ExcludedTermController::class)
    ->names('jobs.excluded-terms')
    ->except('show');
Route::get('/jobs/cron', [ScrapeScheduleController::class, 'index'])->name('jobs.cron');
Route::post('/jobs/cron', [ScrapeScheduleController::class, 'store'])->name('jobs.cron.store');
Route::get('/jobs/cron/{scrapeSchedule}/edit', [ScrapeScheduleController::class, 'edit'])->name('jobs.cron.edit');
Route::put('/jobs/cron/{scrapeSchedule}', [ScrapeScheduleController::class, 'update'])->name('jobs.cron.update');
Route::delete('/jobs/cron/{scrapeSchedule}', [ScrapeScheduleController::class, 'destroy'])->name('jobs.cron.destroy');
Route::post('/jobs/cron/{scrapeSchedule}/run', [ScrapeScheduleController::class, 'run'])->name('jobs.cron.schedule.run');
Route::get('/jobs/cron/run', RunScrapeSchedulesController::class)->name('jobs.cron.run');
Route::get('/cron/run', RunScrapeSchedulesController::class)->name('jobs.cron.run.legacy');
Route::get('/jobs/applied-to', AppliedJobController::class)->name('jobs.applied-to');
