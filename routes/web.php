<?php

use App\Crm\Http\Controllers\CompanyController as CrmCompanyController;
use App\Crm\Http\Controllers\MyBusinessController as CrmMyBusinessController;
use App\Crm\Http\Controllers\PhoneCallController as CrmPhoneCallController;
use App\Crm\Http\Controllers\SavedSearchController as CrmSavedSearchController;
use App\Crm\Http\Controllers\StatusController as CrmStatusController;
use App\Crm\Http\Controllers\ToolsController as CrmToolsController;
use App\Http\Controllers\AppliedJobController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BibleReadingController;
use App\Http\Controllers\BibleReadingPlanController;
use App\Http\Controllers\BibleSearchController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CalendarCountsController;
use App\Http\Controllers\CardChecklistController;
use App\Http\Controllers\CardChecklistItemController;
use App\Http\Controllers\CardCommentController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CareerPathController;
use App\Http\Controllers\CareerPathFileController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ClearDailyJobListingsController;
use App\Http\Controllers\ClearRejectedKanbanController;
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
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsFeedController;
use App\Http\Controllers\PortfolioProjectController;
use App\Http\Controllers\RunScrapeSchedulesController;
use App\Http\Controllers\ScrapeScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
Route::get('/jobs', HomeController::class)->name('jobs.index');
Route::post('/jobs/{date}/not-interested', ClearDailyJobListingsController::class)
    ->where('date', '\\d{4}-\\d{2}-\\d{2}')
    ->name('jobs.daily.not-interested');
Route::view('/jobs/calendar', 'calendar')->name('jobs.calendar');
Route::get('/jobs/calendar/counts', CalendarCountsController::class)->name('jobs.calendar.counts');
Route::get('/jobs/kanban', KanbanController::class)->name('jobs.kanban');
Route::post('/jobs/kanban/rejected/clear', ClearRejectedKanbanController::class)->name('jobs.kanban.rejected.clear');
Route::get('/jobs/search', JobSearchController::class)->name('jobs.search');
Route::post('/jobs/custom', CustomJobController::class)->name('jobs.custom.store');
Route::post('/jobs/{jobListing}/kanban-status', KanbanStatusController::class)->name('jobs.kanban-status');
Route::get('/news', NewsController::class)->name('news');
Route::get('/news/feeds', [NewsFeedController::class, 'index'])->name('news.feeds.index');
Route::post('/news/feeds', [NewsFeedController::class, 'store'])->name('news.feeds.store');
Route::post('/news/feeds/refresh', [NewsFeedController::class, 'refreshAll'])->name('news.feeds.refresh-all');
Route::post('/news/feeds/{feed}/refresh', [NewsFeedController::class, 'refresh'])->name('news.feeds.refresh');
Route::delete('/news/feeds/{feed}', [NewsFeedController::class, 'destroy'])->name('news.feeds.destroy');
Route::get('/bible', [BibleReadingController::class, 'dashboard'])->name('bible');
Route::get('/bible/worksheets', [BibleReadingController::class, 'worksheets'])->name('bible.worksheets');
Route::get('/bible/reading-plans', [BibleReadingPlanController::class, 'index'])->name('bible.plans.index');
Route::post('/bible/reading-plans/days/{day}/complete', [BibleReadingPlanController::class, 'complete'])->name('bible.plans.complete');
Route::get('/bible/search', BibleSearchController::class)->name('bible.search');
Route::put('/bible/{year}/books/{book}/chapters/{chapter}', [BibleReadingController::class, 'update'])
    ->whereNumber('year')
    ->whereNumber('chapter')
    ->name('bible.reading.chapters.update');
Route::get('/todo', [BoardController::class, 'index'])->name('todo');
Route::post('/todo', [BoardController::class, 'store'])->name('todo.boards.store');
Route::get('/todo/{board}', [BoardController::class, 'show'])->name('todo.boards.show');
Route::put('/todo/{board}', [BoardController::class, 'update'])->name('todo.boards.update');
Route::post('/todo/{board}/columns', [BoardColumnController::class, 'store'])->name('todo.columns.store');
Route::patch('/todo/{board}/columns/reorder', [BoardColumnController::class, 'reorder'])->name('todo.columns.reorder');
Route::put('/todo/{board}/columns/{column}', [BoardColumnController::class, 'update'])->name('todo.columns.update');
Route::post('/todo/{board}/columns/{column}/cards', [CardController::class, 'store'])->name('todo.cards.store');
Route::patch('/todo/{board}/cards/{card}/move', [CardController::class, 'move'])->name('todo.cards.move');
Route::put('/todo/{board}/cards/{card}', [CardController::class, 'update'])->name('todo.cards.update');
Route::patch('/todo/{board}/cards/{card}/complete', [CardController::class, 'complete'])->name('todo.cards.complete');
Route::delete('/todo/{board}/cards/{card}', [CardController::class, 'destroy'])->name('todo.cards.destroy');
Route::post('/todo/{board}/cards/{card}/comments', [CardCommentController::class, 'store'])->name('todo.comments.store');
Route::put('/todo/{board}/cards/{card}/comments/{comment}', [CardCommentController::class, 'update'])->name('todo.comments.update');
Route::delete('/todo/{board}/cards/{card}/comments/{comment}', [CardCommentController::class, 'destroy'])->name('todo.comments.destroy');
Route::post('/todo/{board}/cards/{card}/checklists', [CardChecklistController::class, 'store'])->name('todo.checklists.store');
Route::put('/todo/{board}/cards/{card}/checklists/{checklist}', [CardChecklistController::class, 'update'])->name('todo.checklists.update');
Route::delete('/todo/{board}/cards/{card}/checklists/{checklist}', [CardChecklistController::class, 'destroy'])->name('todo.checklists.destroy');
Route::post('/todo/{board}/cards/{card}/checklists/{checklist}/items', [CardChecklistItemController::class, 'store'])->name('todo.checklist-items.store');
Route::put('/todo/{board}/cards/{card}/checklists/{checklist}/items/{item}', [CardChecklistItemController::class, 'update'])->name('todo.checklist-items.update');
Route::patch('/todo/{board}/cards/{card}/checklists/{checklist}/items/{item}/toggle', [CardChecklistItemController::class, 'toggle'])->name('todo.checklist-items.toggle');
Route::delete('/todo/{board}/cards/{card}/checklists/{checklist}/items/{item}', [CardChecklistItemController::class, 'destroy'])->name('todo.checklist-items.destroy');
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
Route::resource('learning/career-paths', CareerPathController::class)
    ->names('learning.career-paths');
Route::get('learning/career-paths/{careerPath}/files/{careerPathFile}', [CareerPathFileController::class, 'show'])
    ->name('learning.career-paths.files.show');
Route::get('learning/career-paths/{careerPath}/files/{careerPathFile}/download', [CareerPathFileController::class, 'download'])
    ->name('learning.career-paths.files.download');
Route::delete('learning/career-paths/{careerPath}/files/{careerPathFile}', [CareerPathFileController::class, 'destroy'])
    ->name('learning.career-paths.files.destroy');
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
Route::post('/jobs/{jobListing}/status', JobListingStatusController::class)->name('jobs.status');
Route::put('/jobs/{jobListing}/details', JobListingDetailsController::class)->name('jobs.details.update');
Route::post('/jobs/{jobListing}/notes', [JobListingNoteController::class, 'store'])->name('jobs.notes.store');
Route::delete('/jobs/{jobListing}/notes/{note}', [JobListingNoteController::class, 'destroy'])->name('jobs.notes.destroy');
Route::post('/jobs/{jobListing}/company-preference', [CompanyPreferenceController::class, 'store'])
    ->name('jobs.company-preference');
Route::get('/jobs/company-preferences', [CompanyPreferenceController::class, 'index'])->name('jobs.company-preferences.index');
Route::get('/jobs/company-preferences/{company}', [CompanyPreferenceController::class, 'show'])
    ->name('jobs.company-preferences.show');
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

Route::prefix(config('crm.route_prefix', 'crm'))
    ->name('crm.')
    ->group(function (): void {
        Route::get('/saved-searches', [CrmSavedSearchController::class, 'index'])->name('saved-searches.index');
        Route::post('/saved-searches', [CrmSavedSearchController::class, 'store'])->name('saved-searches.store');

        Route::get('/my-business', [CrmMyBusinessController::class, 'index'])->name('my-business.index');
        Route::post('/my-business/address', [CrmMyBusinessController::class, 'storeAddress'])->name('my-business.address.store');
        Route::patch('/my-business/{businessProfile}/address', [CrmMyBusinessController::class, 'updateAddress'])->name('my-business.address.update');
        Route::delete('/my-business/{businessProfile}', [CrmMyBusinessController::class, 'destroy'])->name('my-business.destroy');
        Route::get('/my-business/labels/print', [CrmMyBusinessController::class, 'printLabels'])->name('my-business.labels.print');

        Route::view('/tools', 'crm.tools.index')->name('tools.index');
        Route::get('/tools/google-platform', [CrmToolsController::class, 'googlePlatform'])->name('tools.googlePlatform');
        Route::get('/tools/google-platform/oauth/redirect', [CrmToolsController::class, 'redirectGoogleCloudOAuth'])->name('tools.googlePlatform.oauth.redirect');
        Route::get('/tools/google-platform/oauth/callback', [CrmToolsController::class, 'handleGoogleCloudOAuthCallback'])->name('tools.googlePlatform.oauth.callback');
        Route::post('/tools/google-platform/remove', [CrmToolsController::class, 'removeGoogleCloudCredentials'])->name('tools.googlePlatform.remove');
        Route::get('/tools/openai-platform', [CrmToolsController::class, 'openAiPlatform'])->name('tools.openAiPlatform');
        Route::post('/tools/openai-platform/usage-key', [CrmToolsController::class, 'storeOpenAiUsageKey'])->name('tools.openAiPlatform.usageKey.store');
        Route::post('/tools/openai-platform/usage-key/remove', [CrmToolsController::class, 'removeOpenAiUsageKey'])->name('tools.openAiPlatform.usageKey.remove');
        Route::resource('statuses', CrmStatusController::class);

        Route::post('/companies/bulk-action', [CrmCompanyController::class, 'bulkAction'])->name('companies.bulk-action');
        Route::post('/companies/{company}/ai-enrich-clean', [CrmCompanyController::class, 'aiEnrichAndClean'])->name('companies.ai-enrich-clean');
        Route::post('/companies/{company}/ai-opportunity-score', [CrmCompanyController::class, 'aiOpportunityScore'])->name('companies.ai-opportunity-score');
        Route::post('/companies/bulk-tag', [CrmCompanyController::class, 'bulkTag'])->name('companies.bulk-tag');
        Route::get('/companies/data', [CrmCompanyController::class, 'getData'])->name('companies.data');
        Route::get('/companies/google-scrape', [CrmCompanyController::class, 'googleScrape'])->name('companies.googleScrape');
        Route::get('/companies/{company}/modal', [CrmCompanyController::class, 'modal'])->name('companies.modal');
        Route::post('/companies/{company}/notes', [CrmCompanyController::class, 'storeNote'])->name('companies.notes.store');
        Route::post('/companies/{company}/phone-calls', [CrmPhoneCallController::class, 'store'])->name('companies.phone-calls.store');
        Route::patch('/companies/{company}/callback', [CrmCompanyController::class, 'updateCallback'])->name('companies.callback.update');
        Route::patch('/companies/{company}/classification', [CrmCompanyController::class, 'updateClassification'])->name('companies.classification.update');
        Route::get('/companies/sort', [CrmCompanyController::class, 'sortTags'])->name('companies.sort-tags');

        Route::resource('companies', CrmCompanyController::class);
    });
