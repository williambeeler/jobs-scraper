<?php

namespace App\Http\Controllers;

use App\Enums\JobListingOrigin;
use App\Models\JobListing;
use App\Models\NewsArticle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $timezone = (string) config('app.display_timezone', 'America/Chicago');
        $now = Carbon::now($timezone);
        $today = $now->copy()->startOfDay();

        $greetings = match (true) {
            $now->hour < 12 => [
                'Good morning, William!',
                'Rise and shine, William!',
                'Ready for a great day, William?',
            ],
            $now->hour < 17 => [
                'Good afternoon, William!',
                'Welcome back, William!',
                'Keep up the momentum, William!',
            ],
            default => [
                'Good evening, William!',
                'Welcome back, William!',
                'Nice to see you, William!',
            ],
        };

        $dates = collect(range(0, 2))->map(function (int $daysAgo) use ($today): array {
            $date = $today->copy()->subDays($daysAgo);
            $count = JobListing::query()
                ->visible()
                ->where('listing_origin', JobListingOrigin::Scraped->value)
                ->whereNull('status')
                ->whereBetween('created_at', [
                    $date->copy()->startOfDay()->utc(),
                    $date->copy()->endOfDay()->utc(),
                ])
                ->count();

            return [
                'date' => $date,
                'count' => $count,
                'url' => $daysAgo === 0
                    ? route('jobs.index')
                    : route('jobs.index', ['date' => $date->toDateString()]),
            ];
        });

        return view('welcome', [
            'greeting' => $greetings[$today->dayOfYear % count($greetings)],
            'dates' => $dates,
            'newsArticles' => NewsArticle::query()
                ->with('feed')
                ->whereHas('feed', fn ($query) => $query->where('is_active', true))
                ->orderByRaw('published_at IS NULL')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(15)
                ->get(),
        ]);
    }
}
