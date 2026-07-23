<?php

namespace App\Http\Controllers;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Enums\KanbanStatus;
use App\Models\JobListing;
use Illuminate\Contracts\View\View;

class KanbanController extends Controller
{
    public function __invoke(): View
    {
        $columns = collect([
            ['status' => KanbanStatus::Backlog, 'title' => 'Backlog'],
            ['status' => KanbanStatus::Interested, 'title' => 'Interested'],
            ['status' => KanbanStatus::Applied, 'title' => 'Applied To'],
            ['status' => KanbanStatus::Interview, 'title' => 'Interview'],
            ['status' => KanbanStatus::JobWon, 'title' => 'Job Won'],
            ['status' => KanbanStatus::Rejected, 'title' => 'Rejected'],
        ]);

        $listings = JobListing::query()
            ->where(fn ($query) => $query
                ->where('listing_origin', JobListingOrigin::Custom->value)
                ->orWhere(fn ($query) => $query
                    ->where('listing_origin', JobListingOrigin::Scraped->value)
                    ->visible()))
            ->where(fn ($query) => $query
                ->whereNull('status')
                ->orWhere('status', JobListingStatus::Applied))
            ->withExists(['companyPreference as is_whitelisted' => fn ($query) => $query
                ->where('type', CompanyPreferenceType::Whitelist)])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (JobListing $listing) => $listing->kanban_status->value);

        $listings->put(
            KanbanStatus::Backlog->value,
            $listings->get(KanbanStatus::Backlog->value, collect())
                ->sortByDesc(fn (JobListing $listing) => $listing->is_whitelisted)
                ->values(),
        );

        return view('kanban', [
            'columns' => $columns,
            'listingsByStatus' => $listings,
        ]);
    }
}
