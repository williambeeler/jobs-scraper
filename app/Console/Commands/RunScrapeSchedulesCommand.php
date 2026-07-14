<?php

namespace App\Console\Commands;

use App\Models\ScrapeSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunScrapeSchedulesCommand extends Command
{
    protected $signature = 'jobs:run-schedules';

    protected $description = 'Run all enabled scrape configurations that are due';

    public function handle(): int
    {
        $lock = Cache::lock('jobs:run-schedules', 3600);
        if (! $lock->get()) {
            $this->info('Another scheduled scrape check is already running.');

            return self::SUCCESS;
        }

        try {
            $schedules = ScrapeSchedule::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('next_run_at')
                        ->orWhere('next_run_at', '<=', now());
                })
                ->orderBy('next_run_at')
                ->orderBy('id')
                ->get();

            if ($schedules->isEmpty()) {
                $this->info('No scrape configurations are due.');

                return self::SUCCESS;
            }

            $failed = false;
            foreach ($schedules as $schedule) {
                $this->components->task(
                    "Scraping {$schedule->keywords}",
                    function () use ($schedule, &$failed): bool {
                        $completed = $this->runSchedule($schedule);
                        $failed = $failed || ! $completed;

                        return $completed;
                    },
                );
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function runSchedule(ScrapeSchedule $schedule): bool
    {
        try {
            $exitCode = Artisan::call('jobs:scrape', [
                '--keywords' => $schedule->keywords,
                '--days' => $schedule->max_days,
                '--source' => $schedule->sources,
                '--schedule' => $schedule->id,
                '--no-interaction' => true,
            ], $this->output);
        } catch (Throwable $exception) {
            report($exception);
            $exitCode = self::FAILURE;
        }

        $finishedAt = now();
        $schedule->update([
            'last_ran_at' => $finishedAt,
            'next_run_at' => $finishedAt->copy()->addHours($schedule->interval_hours),
        ]);

        return $exitCode === self::SUCCESS;
    }
}
