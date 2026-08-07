<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class AppliedJobController extends Controller
{
    public function __invoke(): View
    {
        $displayTimezone = (string) config('app.display_timezone', 'America/Chicago');
        $listings = JobListing::query()
            ->visible()
            ->where('listing_origin', JobListingOrigin::Scraped->value)
            ->where('status', JobListingStatus::Applied)
            ->withExists(['companyPreference as is_whitelisted' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Whitelist)])
            ->withExists(['companyPreference as is_verified' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Verified)])
            ->with('company')
            ->orderByDesc('status_changed_at')
            ->get();

        $listingGroups = $listings
            ->groupBy(fn (JobListing $listing): string => $listing->status_changed_at
                ? $listing->status_changed_at->timezone($displayTimezone)->toDateString()
                : 'unknown')
            ->map(function ($group, string $date) use ($displayTimezone): array {
                if ($date === 'unknown') {
                    return ['label' => 'Unknown date', 'daysAgo' => null, 'listings' => $group];
                }

                $applicationDate = $group->first()->status_changed_at->timezone($displayTimezone)->startOfDay();

                return [
                    'label' => $applicationDate->format('l, F j, Y'),
                    'daysAgo' => (int) $applicationDate->diffInDays(Carbon::now($displayTimezone)->startOfDay()),
                    'listings' => $group,
                ];
            });

        return view('applied-to', [
            'listings' => $listings,
            'listingGroups' => $listingGroups,
            'displayTimezone' => $displayTimezone,
        ]);
    }
}
