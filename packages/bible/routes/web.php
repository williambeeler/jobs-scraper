<?php

declare(strict_types=1);

use DailyDash\Bible\Http\Controllers\BibleReadingController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::name('bible.')->group(function (): void {
        Route::get('/bible', [BibleReadingController::class, 'index'])->name('reading.index');
        Route::put('/bible/{year}/books/{book}/chapters/{chapter}', [BibleReadingController::class, 'update'])
            ->whereNumber('year')
            ->whereNumber('chapter')
            ->name('reading.chapters.update');
    });
});
