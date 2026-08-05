<?php

namespace App\Http\Controllers;

use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ClearDailyJobListingsController extends Controller
{
    public function __invoke(string $date): RedirectResponse
    {
        Validator::make(['date' => $date], [
            'date' => ['required', 'date_format:Y-m-d'],
        ])->validate();

        $displayTimezone = (string) config('app.display_timezone', 'America/Chicago');
        $selectedDate = Carbon::createFromFormat('!Y-m-d', $date, $displayTimezone);
        $changedAt = now();

        $cleared = JobListing::query()
            ->visible()
            ->where('listing_origin', JobListingOrigin::Scraped)
            ->whereNull('status')
            ->whereBetween('created_at', [
                $selectedDate->copy()->startOfDay()->utc(),
                $selectedDate->copy()->endOfDay()->utc(),
            ])
            ->update([
                'status' => JobListingStatus::NotInterested,
                'status_changed_at' => $changedAt,
            ]);

        $message = trans_choice(
            ':count job marked as not interested.|:count jobs marked as not interested.',
            $cleared,
            ['count' => $cleared],
        );

        return to_route('jobs.index', ['date' => $date])->with('status', $message);
    }
}
