<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarCountsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d', 'after:start'],
        ]);

        $displayTimezone = (string) config('app.display_timezone', 'America/Chicago');
        $start = Carbon::createFromFormat('!Y-m-d', $validated['start'], $displayTimezone);
        $end = Carbon::createFromFormat('!Y-m-d', $validated['end'], $displayTimezone);

        abort_if($start->diffInDays($end) > 62, 422, 'The requested calendar range is too large.');

        $counts = JobListing::query()
            ->visible()
            ->where('listing_origin', JobListingOrigin::Scraped->value)
            ->whereNull('status')
            ->withExists(['companyPreference as is_whitelisted' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Whitelist)])
            ->withExists(['companyPreference as is_verified' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Verified)])
            ->where('created_at', '>=', $start->copy()->utc())
            ->where('created_at', '<', $end->copy()->utc())
            ->get(['id', 'company_id', 'created_at'])
            ->groupBy(fn (JobListing $listing) => $listing->created_at
                ->timezone($displayTimezone)
                ->toDateString())
            ->map(function ($listings, string $date): array {
                $whitelisted = $listings->where('is_whitelisted', true)->count();
                $verified = $listings->where('is_verified', true)->count();

                return [
                    'date' => $date,
                    'whitelisted' => $whitelisted,
                    'verified' => $verified,
                    'regular' => $listings->count() - $whitelisted - $verified,
                ];
            })
            ->values();

        return response()->json($counts);
    }
}
