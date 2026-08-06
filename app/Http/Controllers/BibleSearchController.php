<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BibleTranslation;
use App\Support\BibleReferenceParser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BibleSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'translation' => ['nullable', Rule::enum(BibleTranslation::class)],
        ]);
        $query = trim($validated['q'] ?? '');
        $translation = BibleTranslation::tryFrom($validated['translation'] ?? '') ?? BibleTranslation::default();
        $results = null;
        $searchMode = null;

        if ($query !== '') {
            [$results, $searchMode] = $this->search($query, $translation);
        }

        return view('bible.search', [
            'query' => $query,
            'translation' => $translation,
            'translations' => BibleTranslation::cases(),
            'results' => $results,
            'searchMode' => $searchMode,
        ]);
    }

    /** @return array{0: LengthAwarePaginator, 1: string} */
    private function search(string $search, BibleTranslation $translation): array
    {
        $query = DB::table($translation->table())
            ->select(['book_id', 'book', 'chapter', 'verse', 'text'])
            ->orderBy('book_id')
            ->orderBy('chapter')
            ->orderBy('verse');

        try {
            $passages = BibleReferenceParser::parse($search);
            $query->where(function (Builder $outer) use ($passages): void {
                foreach ($passages as $passage) {
                    $outer->orWhere(function (Builder $reference) use ($passage): void {
                        $reference->where('book_id', $passage['book_id'])
                            ->whereBetween('chapter', [$passage['start_chapter'], $passage['end_chapter']]);

                        if ($passage['start_verse'] !== null) {
                            $reference->where(function (Builder $start) use ($passage): void {
                                $start->where('chapter', '>', $passage['start_chapter'])
                                    ->orWhere(fn (Builder $boundary) => $boundary->where('chapter', $passage['start_chapter'])->where('verse', '>=', $passage['start_verse']));
                            });
                        }
                        if ($passage['end_verse'] !== null) {
                            $reference->where(function (Builder $end) use ($passage): void {
                                $end->where('chapter', '<', $passage['end_chapter'])
                                    ->orWhere(fn (Builder $boundary) => $boundary->where('chapter', $passage['end_chapter'])->where('verse', '<=', $passage['end_verse']));
                            });
                        }
                    });
                }
            });
            $mode = 'reference';
        } catch (Throwable) {
            $query->where('text', 'like', '%'.$search.'%');
            $mode = 'text';
        }

        return [$query->paginate(75)->withQueryString(), $mode];
    }
}
