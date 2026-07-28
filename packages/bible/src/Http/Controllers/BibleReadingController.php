<?php

declare(strict_types=1);

namespace DailyDash\Bible\Http\Controllers;

use DailyDash\Bible\Models\BibleReadingChapter;
use DailyDash\Bible\Support\BibleBooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class BibleReadingController extends Controller
{
    public function index(Request $request): View
    {
        $currentYear = now()->year;
        $selectedYear = $request->integer('year');
        $displayYear = $selectedYear > 0 ? $selectedYear : $currentYear;

        $readings = BibleReadingChapter::query()
            ->where('reading_year', $displayYear)
            ->orderBy('book_slug')
            ->orderBy('chapter_number')
            ->get();

        $readChapters = $readings
            ->groupBy('book_slug')
            ->map(fn ($chapters) => $chapters->pluck('chapter_number')->all())
            ->all();

        return view('bible::reading.index', [
            'booksByTestament' => BibleBooks::grouped(),
            'currentYear' => $currentYear,
            'displayYear' => $displayYear,
            'readChapters' => $readChapters,
            'completedChaptersCount' => $readings->count(),
            'totalChaptersCount' => BibleBooks::totalChapters(),
        ]);
    }

    public function update(Request $request, int $year, string $book, int $chapter): JsonResponse
    {
        abort_unless(BibleBooks::hasBook($book), 404);

        $chapterCount = BibleBooks::chapterCount($book);
        abort_unless($chapterCount !== null && $chapter >= 1 && $chapter <= $chapterCount, 404);

        $read = $request->boolean('read');

        if ($read) {
            BibleReadingChapter::query()->updateOrCreate(
                [
                    'reading_year' => $year,
                    'book_slug' => $book,
                    'chapter_number' => $chapter,
                ],
                [
                    'read_at' => now(),
                ],
            );
        } else {
            BibleReadingChapter::query()
                ->where('reading_year', $year)
                ->where('book_slug', $book)
                ->where('chapter_number', $chapter)
                ->delete();
        }

        $completedCount = BibleReadingChapter::query()
            ->where('reading_year', $year)
            ->count();

        return response()->json([
            'saved' => true,
            'reading_year' => $year,
            'book_slug' => $book,
            'chapter_number' => $chapter,
            'read' => $read,
            'completed_chapters_count' => $completedCount,
            'total_chapters_count' => BibleBooks::totalChapters(),
        ]);
    }
}
