<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BibleTranslation;
use App\Models\BibleReadingChapter;
use App\Support\BibleBooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BibleReadingController extends Controller
{
    public function dashboard(): View
    {
        $currentYear = now()->year;

        return view('bible.index', [
            ...$this->yearData($currentYear, $currentYear, false),
            'query' => '',
            'translation' => BibleTranslation::default(),
            'translations' => BibleTranslation::cases(),
        ]);
    }

    public function worksheets(Request $request): View
    {
        $currentYear = now()->year;
        $selectedYear = $request->integer('year');
        $displayYear = $selectedYear > 0 ? $selectedYear : $currentYear;

        return view('bible.reading.index', $this->yearData($displayYear, $currentYear, true));
    }

    /** @return array<string, mixed> */
    private function yearData(int $displayYear, int $currentYear, bool $includeBooks): array
    {

        $readings = BibleReadingChapter::query()
            ->where('reading_year', $displayYear)
            ->orderBy('book_slug')
            ->orderBy('chapter_number')
            ->get();

        $data = [
            'currentYear' => $currentYear,
            'displayYear' => $displayYear,
            'completedChaptersCount' => $readings->count(),
            'totalChaptersCount' => BibleBooks::totalChapters(),
        ];

        if ($includeBooks) {
            $data['booksByTestament'] = BibleBooks::grouped();
            $data['readChapters'] = $readings
                ->groupBy('book_slug')
                ->map(fn ($chapters) => $chapters->pluck('chapter_number')->all())
                ->all();
        }

        return $data;
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
