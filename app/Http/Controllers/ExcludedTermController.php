<?php

namespace App\Http\Controllers;

use App\Enums\JobListingStatus;
use App\Models\ExcludedTerm;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExcludedTermController extends Controller
{
    public function index(): View
    {
        return view('excluded-terms.index', [
            'terms' => ExcludedTerm::query()->orderBy('term')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $term = $this->validatedTerm($request);
        ExcludedTerm::query()->create(['term' => $term, 'term_key' => ExcludedTerm::key($term)]);
        $this->markMatchingListingsNotInterested($term);

        return to_route('excluded-terms.index')->with('status', 'Excluded term added.');
    }

    public function edit(ExcludedTerm $excludedTerm): View
    {
        return view('excluded-terms.edit', ['excludedTerm' => $excludedTerm]);
    }

    public function update(Request $request, ExcludedTerm $excludedTerm): RedirectResponse
    {
        $term = $this->validatedTerm($request, $excludedTerm);
        $excludedTerm->update(['term' => $term, 'term_key' => ExcludedTerm::key($term)]);
        $this->markMatchingListingsNotInterested($term);

        return to_route('excluded-terms.index')->with('status', 'Excluded term updated.');
    }

    public function destroy(ExcludedTerm $excludedTerm): RedirectResponse
    {
        $excludedTerm->delete();

        return to_route('excluded-terms.index')->with('status', 'Excluded term removed.');
    }

    private function validatedTerm(Request $request, ?ExcludedTerm $excluding = null): string
    {
        $validated = $request->validate(['term' => ['required', 'string', 'max:255']]);
        $term = trim($validated['term']);
        if ($term === '') {
            throw ValidationException::withMessages(['term' => 'Please enter an excluded term.']);
        }
        $key = ExcludedTerm::key($term);

        $exists = ExcludedTerm::query()->where('term_key', $key)
            ->when($excluding, fn ($query) => $query->where('id', '!=', $excluding->id))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['term' => 'This term is already excluded.']);
        }

        return $term;
    }

    private function markMatchingListingsNotInterested(string $term): void
    {
        JobListing::query()->whereRaw('instr(lower(title), ?) > 0', [ExcludedTerm::key($term)])
            ->update(['status' => JobListingStatus::NotInterested, 'status_changed_at' => now()]);
    }
}
