<?php

use Illuminate\Support\Facades\Route;
use WilliamBeeler\Crm\Http\Controllers\CompanyController;
use WilliamBeeler\Crm\Http\Controllers\MyBusinessController;
use WilliamBeeler\Crm\Http\Controllers\PhoneCallController;
use WilliamBeeler\Crm\Http\Controllers\SavedSearchController;
use WilliamBeeler\Crm\Http\Controllers\StatusController;
use WilliamBeeler\Crm\Http\Controllers\ToolsController;

Route::middleware(config('crm.route_middleware', ['web']))
    ->prefix(config('crm.route_prefix', 'crm'))
    ->name('crm.')
    ->group(function (): void {
        Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
        Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');

        Route::get('/my-business', [MyBusinessController::class, 'index'])->name('my-business.index');
        Route::post('/my-business/address', [MyBusinessController::class, 'storeAddress'])->name('my-business.address.store');
        Route::patch('/my-business/{businessProfile}/address', [MyBusinessController::class, 'updateAddress'])->name('my-business.address.update');
        Route::delete('/my-business/{businessProfile}', [MyBusinessController::class, 'destroy'])->name('my-business.destroy');
        Route::get('/my-business/labels/print', [MyBusinessController::class, 'printLabels'])->name('my-business.labels.print');

        Route::get('/tools', [ToolsController::class, 'index'])->name('tools.index');
        Route::get('/tools/google-platform', [ToolsController::class, 'googlePlatform'])->name('tools.googlePlatform');
        Route::get('/tools/google-platform/oauth/redirect', [ToolsController::class, 'redirectGoogleCloudOAuth'])->name('tools.googlePlatform.oauth.redirect');
        Route::get('/tools/google-platform/oauth/callback', [ToolsController::class, 'handleGoogleCloudOAuthCallback'])->name('tools.googlePlatform.oauth.callback');
        Route::post('/tools/google-platform/remove', [ToolsController::class, 'removeGoogleCloudCredentials'])->name('tools.googlePlatform.remove');
        Route::get('/tools/openai-platform', [ToolsController::class, 'openAiPlatform'])->name('tools.openAiPlatform');
        Route::post('/tools/openai-platform/usage-key', [ToolsController::class, 'storeOpenAiUsageKey'])->name('tools.openAiPlatform.usageKey.store');
        Route::post('/tools/openai-platform/usage-key/remove', [ToolsController::class, 'removeOpenAiUsageKey'])->name('tools.openAiPlatform.usageKey.remove');
        Route::get('/tools/import', [ToolsController::class, 'import'])->name('tools.import');
        Route::get('/tools/import/template/company-import-template.csv', [ToolsController::class, 'downloadImportTemplate'])->name('tools.import.template');
        Route::post('/tools/import/companies', [ToolsController::class, 'importCompanies'])->name('tools.import.companies');
        Route::get('/tools/export', [ToolsController::class, 'export'])->name('tools.export');
        Route::post('/tools/exports/start', [ToolsController::class, 'startExport'])->name('tools.exports.start');
        Route::get('/tools/exports/{exportId}/status', [ToolsController::class, 'exportStatus'])->name('tools.exports.status');
        Route::get('/tools/exports/{exportId}/download', [ToolsController::class, 'downloadExport'])->name('tools.exports.download');
        Route::post('/tools/backups/run', [ToolsController::class, 'runDatabaseBackup'])->name('tools.backups.run');
        Route::get('/tools/backups/download', [ToolsController::class, 'downloadBackup'])
            ->middleware('signed')
            ->name('tools.backups.download');

        Route::resource('statuses', StatusController::class);

        Route::post('/companies/bulk-action', [CompanyController::class, 'bulkAction'])->name('companies.bulk-action');
        Route::post('/companies/{company}/ai-enrich-clean', [CompanyController::class, 'aiEnrichAndClean'])->name('companies.ai-enrich-clean');
        Route::post('/companies/{company}/ai-opportunity-score', [CompanyController::class, 'aiOpportunityScore'])->name('companies.ai-opportunity-score');
        Route::post('/companies/bulk-tag', [CompanyController::class, 'bulkTag'])->name('companies.bulk-tag');
        Route::get('/companies/data', [CompanyController::class, 'getData'])->name('companies.data');
        Route::get('/companies/google-scrape', [CompanyController::class, 'googleScrape'])->name('companies.googleScrape');
        Route::get('/companies/{company}/modal', [CompanyController::class, 'modal'])->name('companies.modal');
        Route::post('/companies/{company}/notes', [CompanyController::class, 'storeNote'])->name('companies.notes.store');
        Route::post('/companies/{company}/phone-calls', [PhoneCallController::class, 'store'])->name('companies.phone-calls.store');
        Route::patch('/companies/{company}/callback', [CompanyController::class, 'updateCallback'])->name('companies.callback.update');
        Route::patch('/companies/{company}/classification', [CompanyController::class, 'updateClassification'])->name('companies.classification.update');
        Route::get('/companies/sort', [CompanyController::class, 'sortTags'])->name('companies.sort-tags');

        Route::resource('companies', CompanyController::class);
    });
