<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BibleReadingPlan;
use App\Support\BibleReferenceParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BibleReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/bible-reading-plans.json')), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            foreach ($data as $planData) {
                $plan = BibleReadingPlan::query()->updateOrCreate(
                    ['slug' => $planData['slug']],
                    collect($planData)->only(['name', 'description', 'duration_years'])->all(),
                );
                foreach ($planData['days'] as $dayData) {
                    $day = $plan->days()->updateOrCreate(
                        [
                            'cycle_year' => $dayData['cycle_year'] ?? 1,
                            'month' => $dayData['month'],
                            'month_day' => $dayData['month_day'],
                        ],
                        [
                            'day_number' => $dayData['day'] ?? null,
                            'is_catch_up' => $dayData['is_catch_up'] ?? false,
                        ],
                    );
                    $day->passages()->delete();
                    $order = 0;
                    foreach ($dayData['passages'] as $reference) {
                        try {
                            $passages = BibleReferenceParser::parse($reference);
                        } catch (\Throwable $exception) {
                            throw new RuntimeException("Could not import {$plan->slug} {$day->month}/{$day->month_day}: {$reference}", previous: $exception);
                        }
                        foreach ($passages as $passage) {
                            $day->passages()->create([...$passage, 'display_order' => ++$order]);
                        }
                    }
                }
            }
        });
    }
}
