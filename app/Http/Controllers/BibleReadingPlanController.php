<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BibleTranslation;
use App\Models\BibleReadingChapter;
use App\Models\BibleReadingPlan;
use App\Models\BibleReadingPlanCompletion;
use App\Models\BibleReadingPlanDay;
use App\Support\BibleBooks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BibleReadingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = BibleReadingPlan::query()->orderBy('id')->get();
        $selectedPlan = $plans->firstWhere('slug', $request->string('plan')->toString()) ?? $plans->first();
        $translation = BibleTranslation::tryFrom($request->string('translation')->toString()) ?? BibleTranslation::default();
        $cycleYear = max(1, min((int) ($selectedPlan?->duration_years ?? 1), $request->integer('cycle', 1)));
        $today = now();
        $day = $selectedPlan?->days()
            ->with(['passages', 'completions' => fn ($query) => $query->where('reading_year', $today->year)])
            ->where('cycle_year', $cycleYear)
            ->where('month', $today->month)
            ->where('month_day', $today->day)
            ->first();

        return view('bible.plans.index', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'translations' => BibleTranslation::cases(),
            'translation' => $translation,
            'cycleYear' => $cycleYear,
            'today' => $today,
            'day' => $day,
            'readings' => $day ? $this->readings($day, $translation) : collect(),
            'completed' => $day?->completions->isNotEmpty() ?? false,
        ]);
    }

    public function complete(Request $request, BibleReadingPlanDay $day): RedirectResponse
    {
        $validated = $request->validate([
            'translation' => ['required', Rule::enum(BibleTranslation::class)],
            'plan' => ['required', 'string'],
            'cycle' => ['required', 'integer', 'min:1', 'max:2'],
        ]);
        $year = now()->year;
        $day->loadMissing('passages');

        DB::transaction(function () use ($day, $year): void {
            BibleReadingPlanCompletion::query()->updateOrCreate(
                ['bible_reading_plan_day_id' => $day->id, 'reading_year' => $year],
                ['completed_at' => now()],
            );
            foreach ($day->passages as $passage) {
                for ($chapter = $passage->start_chapter; $chapter <= $passage->end_chapter; $chapter++) {
                    BibleReadingChapter::query()->updateOrCreate(
                        ['reading_year' => $year, 'book_slug' => $passage->book_slug, 'chapter_number' => $chapter],
                        ['read_at' => now()],
                    );
                }
            }
        });

        return redirect()->route('bible.plans.index', $validated)->with('status', 'Today’s reading is complete, and every touched chapter was checked off in this year’s worksheet.');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function readings(BibleReadingPlanDay $day, BibleTranslation $translation): Collection
    {
        return $day->passages->map(function ($passage) use ($translation): array {
            $verses = DB::table($translation->table())
                ->where('book_id', $passage->book_id)
                ->whereBetween('chapter', [$passage->start_chapter, $passage->end_chapter])
                ->orderBy('chapter')->orderBy('verse')->get()
                ->filter(function ($verse) use ($passage): bool {
                    if ($verse->chapter === $passage->start_chapter && $passage->start_verse !== null && $verse->verse < $passage->start_verse) {
                        return false;
                    }

                    return ! ($verse->chapter === $passage->end_chapter && $passage->end_verse !== null && $verse->verse > $passage->end_verse);
                });
            $book = BibleBooks::find($passage->book_slug);

            return ['passage' => $passage, 'book' => $book, 'chapters' => $verses->groupBy('chapter')];
        });
    }
}
