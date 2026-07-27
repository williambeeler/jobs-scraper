<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $displayTimezone = (string) config('app.display_timezone', 'America/Chicago');
        $selectedDate = isset($validated['date'])
            ? Carbon::createFromFormat('!Y-m-d', $validated['date'], $displayTimezone)
            : Carbon::now($displayTimezone)->startOfDay();

        $listings = JobListing::query()
            ->visible()
            ->where('listing_origin', JobListingOrigin::Scraped->value)
            ->whereNull('status')
            ->with('notes')
            ->withExists(['companyPreference as is_whitelisted' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Whitelist)])
            ->withExists(['companyPreference as is_verified' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Verified)])
            ->whereBetween('created_at', [
                $selectedDate->copy()->startOfDay()->utc(),
                $selectedDate->copy()->endOfDay()->utc(),
            ])
            ->orderByDesc('is_whitelisted')
            ->orderByDesc('is_verified')
            ->orderByDesc('posted_at')
            ->orderByDesc('last_seen_at')
            ->get();

        return view('home', [
            'selectedDate' => $selectedDate,
            'isToday' => $selectedDate->isToday(),
            'displayTimezone' => $displayTimezone,
            'listings' => $listings,
        ]);
    }
}
