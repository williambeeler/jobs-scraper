<?php

namespace App\Crm\Services;

use App\Crm\Models\GooglePlacesUsageLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GooglePlacesUsageDashboardService
{
    private const MonthlyPointLimit = 10000;

    /**
     * @return array{
     *     selectedRangeDays: int,
     *     availableRangeDays: array<int, int>,
     *     tiers: array<int, array{value: string, label: string, points: int}>,
     *     usageChart: array{range_label: string, labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, int>, border_color: string, background_color: string}>},
     *     monthlyUsage: array{month_label: string, points_used: int, point_limit: int, points_remaining: int, percent_used: int, request_count: int}
     * }
     */
    public function getDashboardData(int $rangeDays = 30): array
    {
        $selectedRangeDays = in_array($rangeDays, $this->availableRangeDays(), true) ? $rangeDays : 30;
        $range = $this->buildRange($selectedRangeDays);
        $logs = GooglePlacesUsageLog::query()
            ->whereBetween('usage_date', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->orderBy('usage_date')
            ->get();

        return [
            'selectedRangeDays' => $selectedRangeDays,
            'availableRangeDays' => $this->availableRangeDays(),
            'tiers' => collect(GooglePlacesUsageLog::supportedTiers())
                ->map(fn (string $tier): array => [
                    'value' => $tier,
                    'label' => GooglePlacesUsageLog::displayLabel($tier),
                    'points' => GooglePlacesUsageLog::pointsForTier($tier),
                ])
                ->values()
                ->all(),
            'usageChart' => $this->buildUsageChart($logs, $range['start'], $range['end']),
            'monthlyUsage' => $this->buildMonthlyUsage(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function availableRangeDays(): array
    {
        return [30, 90, 180, 365];
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    private function buildRange(int $rangeDays): array
    {
        $end = now()->startOfDay();
        $start = $end->copy()->subDays($rangeDays - 1);

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('F j, Y').' to '.$end->format('F j, Y'),
        ];
    }

    /**
     * @param  Collection<int, GooglePlacesUsageLog>  $logs
     * @return array{range_label: string, labels: array<int, string>, datasets: array<int, array{label: string, data: array<int, int>, border_color: string, background_color: string}>}
     */
    private function buildUsageChart(Collection $logs, CarbonInterface $start, CarbonInterface $end): array
    {
        $dates = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dates->push($cursor->copy());
            $cursor = $cursor->addDay();
        }

        $labels = $dates->map(fn (CarbonInterface $date): string => $date->format('M d'))->all();
        $dailyUsage = $logs->groupBy(fn (GooglePlacesUsageLog $log): string => $log->usage_date->toDateString());
        $palette = [
            GooglePlacesUsageLog::TierEssentials => [
                'border_color' => 'rgba(66, 133, 244, 1)',
                'background_color' => 'rgba(66, 133, 244, 0.16)',
            ],
            GooglePlacesUsageLog::TierPro => [
                'border_color' => 'rgba(52, 168, 83, 1)',
                'background_color' => 'rgba(52, 168, 83, 0.18)',
            ],
            GooglePlacesUsageLog::TierEnterprise => [
                'border_color' => 'rgba(234, 67, 53, 1)',
                'background_color' => 'rgba(234, 67, 53, 0.18)',
            ],
        ];

        $datasets = collect(GooglePlacesUsageLog::supportedTiers())
            ->map(function (string $tier) use ($dates, $dailyUsage, $palette): array {
                $data = $dates->map(function (CarbonInterface $date) use ($dailyUsage, $tier): int {
                    return collect($dailyUsage->get($date->toDateString(), []))
                        ->where('tier', $tier)
                        ->sum('google_request_count');
                })->all();

                return [
                    'label' => GooglePlacesUsageLog::displayLabel($tier),
                    'data' => $data,
                    'border_color' => $palette[$tier]['border_color'],
                    'background_color' => $palette[$tier]['background_color'],
                ];
            })
            ->values()
            ->all();

        return [
            'range_label' => $start->format('F j, Y').' to '.$end->format('F j, Y'),
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @return array{month_label: string, points_used: int, point_limit: int, points_remaining: int, percent_used: int, request_count: int}
     */
    private function buildMonthlyUsage(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $monthlyLogs = GooglePlacesUsageLog::query()
            ->whereBetween('usage_date', [$start->toDateString(), $end->toDateString()])
            ->get();
        $pointsUsed = (int) $monthlyLogs->sum('points_used');
        $requestCount = (int) $monthlyLogs->sum('google_request_count');
        $pointsRemaining = max(self::MonthlyPointLimit - $pointsUsed, 0);
        $percentUsed = self::MonthlyPointLimit > 0
            ? (int) min(round(($pointsUsed / self::MonthlyPointLimit) * 100), 100)
            : 0;

        return [
            'month_label' => $start->format('F Y'),
            'points_used' => $pointsUsed,
            'point_limit' => self::MonthlyPointLimit,
            'points_remaining' => $pointsRemaining,
            'percent_used' => $percentUsed,
            'request_count' => $requestCount,
        ];
    }
}
