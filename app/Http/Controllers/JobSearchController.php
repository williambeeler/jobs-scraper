<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $hasSearched = $request->hasAny([
            'q',
            'company_type',
            'company',
            'source',
            'location',
            'salary',
            'salary_text',
            'status',
            'origin',
            'posted_from',
            'posted_to',
            'per_page',
        ]);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'company_type' => ['nullable', Rule::in(['any', 'whitelist', 'verified', 'blacklist', 'none'])],
            'company' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', Rule::in(['any', 'provided', 'missing'])],
            'salary_text' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['any', 'active', 'applied', 'not_interested'])],
            'origin' => ['nullable', Rule::in(['any', 'scraped', 'custom'])],
            'posted_from' => ['nullable', 'date_format:Y-m-d'],
            'posted_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:posted_from'],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
        ]);

        $filters = [
            'q' => trim($validated['q'] ?? ''),
            'company_type' => $validated['company_type'] ?? 'any',
            'company' => trim($validated['company'] ?? ''),
            'source' => $validated['source'] ?? '',
            'location' => trim($validated['location'] ?? ''),
            'salary' => $validated['salary'] ?? 'any',
            'salary_text' => trim($validated['salary_text'] ?? ''),
            'status' => $validated['status'] ?? 'any',
            'origin' => $validated['origin'] ?? 'any',
            'posted_from' => $validated['posted_from'] ?? '',
            'posted_to' => $validated['posted_to'] ?? '',
            'per_page' => (int) ($validated['per_page'] ?? 50),
        ];

        $query = JobListing::query()
            ->with(['company.preference'])
            ->when(! $hasSearched, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($filters['q'], function (Builder $query, string $search): void {
                $terms = preg_split('/\s+/', $search, flags: PREG_SPLIT_NO_EMPTY) ?: [];

                foreach ($terms as $term) {
                    $query->where(function (Builder $query) use ($term): void {
                        $like = "%{$term}%";
                        $query->where('title', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhere('source', 'like', $like)
                            ->orWhere('salary', 'like', $like)
                            ->orWhereHas('company', fn (Builder $query) => $query->where('name', 'like', $like));
                    });
                }
            })
            ->when($filters['company_type'] !== 'any', function (Builder $query) use ($filters): void {
                if ($filters['company_type'] === 'none') {
                    $query->whereDoesntHave('company.preference');

                    return;
                }

                $type = CompanyPreferenceType::from($filters['company_type']);
                $query->whereHas('company.preference', fn (Builder $query) => $query->where('type', $type));
            })
            ->when($filters['company'], fn (Builder $query, string $company) => $query
                ->whereHas('company', fn (Builder $query) => $query->where('name', 'like', "%{$company}%")))
            ->when($filters['source'], fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['location'], fn (Builder $query, string $location) => $query->where('location', 'like', "%{$location}%"))
            ->when($filters['salary'] === 'provided', fn (Builder $query) => $query->whereNotNull('salary')->where('salary', '!=', ''))
            ->when($filters['salary'] === 'missing', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('salary')->orWhere('salary', '')))
            ->when($filters['salary_text'], fn (Builder $query, string $salary) => $query->where('salary', 'like', "%{$salary}%"))
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->whereNull('status'))
            ->when(in_array($filters['status'], ['applied', 'not_interested'], true), fn (Builder $query) => $query->where('status', JobListingStatus::from($filters['status'])))
            ->when(in_array($filters['origin'], ['scraped', 'custom'], true), fn (Builder $query) => $query->where('listing_origin', JobListingOrigin::from($filters['origin'])))
            ->when($filters['posted_from'], fn (Builder $query, string $date) => $query->whereDate('posted_at', '>=', $date))
            ->when($filters['posted_to'], fn (Builder $query, string $date) => $query->whereDate('posted_at', '<=', $date));

        $listings = $query
            ->orderByDesc('posted_at')
            ->orderByDesc('last_seen_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('job-search', [
            'filters' => $filters,
            'hasSearched' => $hasSearched,
            'listings' => $listings,
            'sources' => JobListing::query()->distinct()->orderBy('source')->pluck('source'),
            'totalJobs' => JobListing::query()->count(),
        ]);
    }
}
