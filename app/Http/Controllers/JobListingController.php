<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobListingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:32'],
        ]);

        $listings = JobListing::query()
            ->visible()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('company', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($filters['source'] ?? null, fn ($query, string $source) => $query->where('source', $source))
            ->orderByDesc('posted_at')
            ->orderByDesc('last_seen_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (JobListing $listing): array => $this->listingData($listing));

        return Inertia::render('job-listings/Index', [
            'listings' => $listings,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'source' => $filters['source'] ?? '',
            ],
            'sources' => JobListing::query()->distinct()->orderBy('source')->pluck('source'),
        ]);
    }

    public function show(JobListing $jobListing): Response
    {
        abort_unless(JobListing::query()->visible()->whereKey($jobListing)->exists(), 404);
        return Inertia::render('job-listings/Show', [
            'listing' => $this->listingData($jobListing),
        ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function listingData(JobListing $listing): array
    {
        return [
            'id' => $listing->id,
            'source' => $listing->source,
            'title' => $listing->title,
            'company' => $listing->company,
            'location' => $listing->location,
            'url' => $listing->url,
            'posted_at' => $listing->posted_at?->toDateString(),
            'posted_relative' => $listing->posted_relative,
            'salary' => $listing->salary,
            'first_seen_at' => $listing->first_seen_at->toIso8601String(),
            'last_seen_at' => $listing->last_seen_at->toIso8601String(),
        ];
    }
}
