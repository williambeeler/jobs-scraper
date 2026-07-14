<?php

namespace App\Http\Controllers;

use App\Models\ScrapeSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScrapeScheduleController extends Controller
{
    public function index(): View
    {
        return view('cron', [
            'schedules' => ScrapeSchedule::query()
                ->orderByDesc('is_active')
                ->orderBy('keywords')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keywords' => ['required', 'string', 'max:255'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['required', 'string', Rule::in(['linkedin', 'indeed', 'ziprecruiter'])],
            'max_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interval_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'is_active' => ['required', 'boolean'],
        ]);

        $keywords = trim($validated['keywords']);
        $sources = array_values(array_unique($validated['sources']));
        sort($sources);
        $configurationHash = hash('sha256', mb_strtolower($keywords).'|'.implode(',', $sources));

        $schedule = ScrapeSchedule::query()->updateOrCreate(
            ['configuration_hash' => $configurationHash],
            [
                'keywords' => $keywords,
                'sources' => $sources,
                'max_days' => $validated['max_days'],
                'interval_hours' => $validated['interval_hours'],
                'is_active' => $validated['is_active'],
            ],
        );

        return to_route('cron')->with(
            'status',
            $schedule->wasRecentlyCreated ? 'Scrape configuration saved.' : 'Scrape configuration updated.',
        );
    }
}
