<?php

namespace App\Http\Controllers;

use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;

class AppliedJobController extends Controller
{
    public function __invoke(): View
    {
        return view('applied-to', [
            'listings' => JobListing::query()
                ->where('status', JobListingStatus::Applied)
                ->orderByDesc('status_changed_at')
                ->get(),
            'displayTimezone' => (string) config('app.display_timezone', 'America/Chicago'),
        ]);
    }
}
