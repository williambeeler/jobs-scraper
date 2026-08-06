<?php

namespace App\Http\Controllers;

use App\Models\ScrapeSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ScrapeScheduleController extends Controller
{
    public function index(): View
    {
        return $this->view();
    }

    public function edit(ScrapeSchedule $scrapeSchedule): View
    {
        return $this->view($scrapeSchedule);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);

        $schedule = ScrapeSchedule::query()->updateOrCreate(
            ['configuration_hash' => $attributes['configuration_hash']],
            $attributes,
        );

        return to_route('jobs.cron')->with(
            'status',
            $schedule->wasRecentlyCreated ? 'Scrape configuration saved.' : 'Scrape configuration updated.',
        );
    }

    public function update(Request $request, ScrapeSchedule $scrapeSchedule): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);
        $duplicateExists = ScrapeSchedule::query()
            ->where('configuration_hash', $attributes['configuration_hash'])
            ->whereKeyNot($scrapeSchedule->id)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'keywords' => 'A scrape configuration with these keywords and sources already exists.',
            ]);
        }

        $scrapeSchedule->update($attributes);

        return to_route('jobs.cron')->with('status', 'Scrape configuration updated.');
    }

    public function destroy(ScrapeSchedule $scrapeSchedule): RedirectResponse
    {
        $scrapeSchedule->delete();

        return to_route('jobs.cron')->with('status', 'Scrape configuration deleted.');
    }

    public function run(ScrapeSchedule $scrapeSchedule): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('jobs:scrape', [
                '--keywords' => $scrapeSchedule->keywords,
                '--days' => $scrapeSchedule->max_days,
                '--source' => $scrapeSchedule->sources,
                '--schedule' => $scrapeSchedule->id,
                '--no-interaction' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $exitCode = 1;
        }

        $finishedAt = now();
        $scrapeSchedule->update([
            'last_ran_at' => $finishedAt,
            'next_run_at' => $finishedAt->copy()->addHours($scrapeSchedule->interval_hours),
        ]);

        if ($exitCode !== 0) {
            return to_route('jobs.cron')->with('error', 'Scrape configuration failed. Check the latest scrape run for details.');
        }

        return to_route('jobs.cron')->with('status', 'Scrape configuration completed.');
    }

    private function view(?ScrapeSchedule $editingSchedule = null): View
    {
        return view('cron', [
            'schedules' => ScrapeSchedule::query()
                ->orderByDesc('is_active')
                ->orderBy('keywords')
                ->get(),
            'editingSchedule' => $editingSchedule,
        ]);
    }

    /**
     * @return array{keywords: string, sources: string[], configuration_hash: string, max_days: int, interval_hours: int, is_active: bool}
     */
    private function validatedAttributes(Request $request): array
    {
        $validated = $request->validate([
            'keywords' => [
                Rule::requiredIf(fn (): bool => array_intersect(
                    (array) $request->input('sources', []),
                    ['linkedin', 'dice', 'working_nomads', 'usajobs'],
                ) !== []),
                'nullable',
                'string',
                'max:255',
            ],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['required', 'string', Rule::in([
                'linkedin',
                'dice',
                'working_nomads',
                'wordpress_jobs',
                'larajobs',
                'usajobs',
            ])],
            'max_days' => ['required', 'integer', 'min:1', 'max:365'],
            'interval_hours' => ['required', 'integer', 'min:1', 'max:8760'],
            'is_active' => ['required', 'boolean'],
        ]);

        $keywords = trim((string) ($validated['keywords'] ?? ''));
        $sources = array_values(array_unique($validated['sources']));
        sort($sources);

        return [
            'keywords' => $keywords,
            'sources' => $sources,
            'configuration_hash' => hash('sha256', mb_strtolower($keywords).'|'.implode(',', $sources)),
            'max_days' => (int) $validated['max_days'],
            'interval_hours' => (int) $validated['interval_hours'],
            'is_active' => (bool) $validated['is_active'],
        ];
    }
}
