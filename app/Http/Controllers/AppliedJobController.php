<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;

class AppliedJobController extends Controller
{
    public function __invoke(): View
    {
        return view('applied-to', [
            'listings' => JobListing::query()
                ->visible()
                ->where('status', JobListingStatus::Applied)
                ->withExists(['companyPreference as is_whitelisted' => fn ($query) => $query
                    ->where('type', CompanyPreferenceType::Whitelist)])
                ->orderByDesc('status_changed_at')
                ->get(),
            'displayTimezone' => (string) config('app.display_timezone', 'America/Chicago'),
        ]);
    }
}
