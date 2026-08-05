<?php

namespace App\Crm\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAiPlatformService
{
    /**
     * @return array{
     *     hasApiKey: bool,
     *     hasUsageApiKey: bool,
     *     identity: array{name: ?string, email: ?string, role: ?string, organization_title: ?string, has_payg_project_spend_limit: ?bool},
     *     costs: array{status: string, total: ?float, currency: ?string, message: string, range_label: string, daily: array<int, array{label: string, value: float}>},
     *     usage: array{status: string, requests: ?int, input_tokens: ?int, output_tokens: ?int, message: string, range_label: string, daily_requests: array<int, array{label: string, value: int}>, daily_tokens: array<int, array{label: string, value: int}>},
     *     keyModeLabel: string
     * }
     */
    public function getDashboardData(): array
    {
        $hasApiKey = filled($this->standardApiKey());
        $hasUsageApiKey = filled($this->usageApiKey());

        if (! $hasApiKey) {
            return [
                'hasApiKey' => false,
                'hasUsageApiKey' => false,
                'keyModeLabel' => 'Missing',
                'identity' => [
                    'name' => null,
                    'email' => null,
                    'role' => null,
                    'organization_title' => null,
                    'has_payg_project_spend_limit' => null,
                ],
                'costs' => $this->emptyState('missing_key', 'Add an OpenAI API key before this page can inspect account status.'),
                'usage' => $this->emptyUsageState('missing_key', 'Add an OpenAI API key before this page can inspect usage trends.'),
            ];
        }

        return Cache::remember(
            $this->cacheKey(),
            now()->addHour(),
            function () use ($hasApiKey, $hasUsageApiKey): array {
                return [
                    'hasApiKey' => $hasApiKey,
                    'hasUsageApiKey' => $hasUsageApiKey,
                    'keyModeLabel' => $hasUsageApiKey ? 'Usage key configured' : 'Standard key only',
                    'identity' => $this->fetchIdentity(),
                    'costs' => $this->fetchCosts(),
                    'usage' => $this->fetchUsage(),
                ];
            }
        );
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{name: ?string, email: ?string, role: ?string, organization_title: ?string, has_payg_project_spend_limit: ?bool}
     */
    private function fetchIdentity(): array
    {
        try {
            $response = $this->client()->get('/me');
        } catch (Throwable) {
            return [
                'name' => null,
                'email' => null,
                'role' => null,
                'organization_title' => null,
                'has_payg_project_spend_limit' => null,
            ];
        }

        if (! $response->ok()) {
            return [
                'name' => null,
                'email' => null,
                'role' => null,
                'organization_title' => null,
                'has_payg_project_spend_limit' => null,
            ];
        }

        $payload = $response->json();
        $organization = collect(data_get($payload, 'orgs.data', []))->firstWhere('is_default', true);

        return [
            'name' => data_get($payload, 'name'),
            'email' => data_get($payload, 'email'),
            'role' => is_array($organization) ? data_get($organization, 'role') : null,
            'organization_title' => is_array($organization) ? data_get($organization, 'title') : null,
            'has_payg_project_spend_limit' => data_get($payload, 'has_payg_project_spend_limit'),
        ];
    }

    /**
     * @return array{status: string, total: ?float, currency: ?string, message: string, range_label: string, daily: array<int, array{label: string, value: float}>}
     */
    private function fetchCosts(): array
    {
        $range = $this->currentMonthRange();
        try {
            $response = $this->client($this->usageApiKey() ?: $this->standardApiKey())
                ->get('/organization/costs', [
                    'start_time' => $range['start']->timestamp,
                    'end_time' => $range['end']->timestamp,
                    'bucket_width' => '1d',
                    'limit' => 31,
                ]);
        } catch (Throwable) {
            return [
                'status' => 'error',
                'total' => null,
                'currency' => null,
                'message' => 'OpenAI could not be reached while loading cost data.',
                'range_label' => $range['label'],
                'daily' => [],
            ];
        }

        if ($response->status() === 403) {
            return [
                'status' => 'forbidden',
                'total' => null,
                'currency' => null,
                'message' => $this->extractOpenAiError($response, 'This key cannot read organization cost data yet.'),
                'range_label' => $range['label'],
                'daily' => [],
            ];
        }

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'total' => null,
                'currency' => null,
                'message' => $this->extractOpenAiError($response, 'Cost data could not be loaded.'),
                'range_label' => $range['label'],
                'daily' => [],
            ];
        }

        $buckets = collect($response->json('data', []))->values();
        $results = $buckets
            ->flatMap(static fn (array $bucket): array => $bucket['results'] ?? [])
            ->values();

        $total = $results->sum(static fn (array $result): float => (float) data_get($result, 'amount.value', 0));
        $currency = $results
            ->map(static fn (array $result): ?string => data_get($result, 'amount.currency'))
            ->first(static fn (?string $value): bool => filled($value));
        $daily = $buckets
            ->map(function (array $bucket): array {
                $value = collect($bucket['results'] ?? [])
                    ->sum(static fn (array $result): float => (float) data_get($result, 'amount.value', 0));

                return [
                    'label' => Carbon::createFromTimestamp((int) data_get($bucket, 'start_time'))->format('M d'),
                    'value' => round($value, 4),
                ];
            })
            ->all();

        return [
            'status' => 'available',
            'total' => round($total, 4),
            'currency' => $currency,
            'message' => $results->isEmpty()
                ? 'No billed cost has been recorded in this range.'
                : 'Current month cost pulled from the organization costs endpoint.',
            'range_label' => $range['label'],
            'daily' => $daily,
        ];
    }

    /**
     * @return array{status: string, requests: ?int, input_tokens: ?int, output_tokens: ?int, message: string, range_label: string, daily_requests: array<int, array{label: string, value: int}>, daily_tokens: array<int, array{label: string, value: int}>}
     */
    private function fetchUsage(): array
    {
        $range = $this->currentMonthRange();
        try {
            $response = $this->client($this->usageApiKey() ?: $this->standardApiKey())
                ->get('/organization/usage/completions', [
                    'start_time' => $range['start']->timestamp,
                    'end_time' => $range['end']->timestamp,
                    'bucket_width' => '1d',
                    'limit' => 31,
                ]);
        } catch (Throwable) {
            return [
                'status' => 'error',
                'requests' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'message' => 'OpenAI could not be reached while loading usage data.',
                'range_label' => $range['label'],
                'daily_requests' => [],
                'daily_tokens' => [],
            ];
        }

        if ($response->status() === 403) {
            return [
                'status' => 'forbidden',
                'requests' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'message' => $this->extractOpenAiError($response, 'This key cannot read organization usage data yet.'),
                'range_label' => $range['label'],
                'daily_requests' => [],
                'daily_tokens' => [],
            ];
        }

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'requests' => null,
                'input_tokens' => null,
                'output_tokens' => null,
                'message' => $this->extractOpenAiError($response, 'Usage data could not be loaded.'),
                'range_label' => $range['label'],
                'daily_requests' => [],
                'daily_tokens' => [],
            ];
        }

        $buckets = collect($response->json('data', []))->values();
        $results = $buckets
            ->flatMap(static fn (array $bucket): array => $bucket['results'] ?? [])
            ->values();
        $dailyRequests = $buckets
            ->map(function (array $bucket): array {
                $value = collect($bucket['results'] ?? [])
                    ->sum(static fn (array $result): int => (int) data_get($result, 'num_model_requests', 0));

                return [
                    'label' => Carbon::createFromTimestamp((int) data_get($bucket, 'start_time'))->format('M d'),
                    'value' => $value,
                ];
            })
            ->all();
        $dailyTokens = $buckets
            ->map(function (array $bucket): array {
                $value = collect($bucket['results'] ?? [])
                    ->sum(static fn (array $result): int => (int) data_get($result, 'input_tokens', 0) + (int) data_get($result, 'output_tokens', 0));

                return [
                    'label' => Carbon::createFromTimestamp((int) data_get($bucket, 'start_time'))->format('M d'),
                    'value' => $value,
                ];
            })
            ->all();

        return [
            'status' => 'available',
            'requests' => $results->sum(static fn (array $result): int => (int) data_get($result, 'num_model_requests', 0)),
            'input_tokens' => $results->sum(static fn (array $result): int => (int) data_get($result, 'input_tokens', 0)),
            'output_tokens' => $results->sum(static fn (array $result): int => (int) data_get($result, 'output_tokens', 0)),
            'message' => $results->isEmpty()
                ? 'No completions usage has been recorded in this range.'
                : 'Current month completions usage pulled from the organization usage endpoint.',
            'range_label' => $range['label'],
            'daily_requests' => $dailyRequests,
            'daily_tokens' => $dailyTokens,
        ];
    }

    /**
     * @return array{status: string, total: ?float, currency: ?string, message: string, range_label: string, daily: array<int, array{label: string, value: float}>}
     */
    private function emptyState(string $status, string $message): array
    {
        return [
            'status' => $status,
            'total' => null,
            'currency' => null,
            'message' => $message,
            'range_label' => $this->currentMonthRange()['label'],
            'daily' => [],
        ];
    }

    /**
     * @return array{status: string, requests: ?int, input_tokens: ?int, output_tokens: ?int, message: string, range_label: string, daily_requests: array<int, array{label: string, value: int}>, daily_tokens: array<int, array{label: string, value: int}>}
     */
    private function emptyUsageState(string $status, string $message): array
    {
        return [
            'status' => $status,
            'requests' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'message' => $message,
            'range_label' => $this->currentMonthRange()['label'],
            'daily_requests' => [],
            'daily_tokens' => [],
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    private function currentMonthRange(): array
    {
        $start = now()->startOfMonth();
        $end = now();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('F j, Y').' to '.$end->format('F j, Y'),
        ];
    }

    private function extractOpenAiError($response, string $fallback): string
    {
        $error = $response->json('error');

        if (is_string($error) && $error !== '') {
            return $error;
        }

        if (is_array($error)) {
            $message = data_get($error, 'message');

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return $fallback;
    }

    private function standardApiKey(): ?string
    {
        return config('services.openai.api_key');
    }

    private function usageApiKey(): ?string
    {
        return config('services.openai.usage_api_key');
    }

    private function client(?string $apiKey = null)
    {
        $resolvedApiKey = $apiKey ?: $this->standardApiKey();

        return Http::baseUrl(rtrim((string) config('services.openai.url'), '/'))
            ->timeout(20)
            ->acceptJson()
            ->withHeaders(array_filter([
                'Authorization' => 'Bearer '.$resolvedApiKey,
                'OpenAI-Organization' => config('services.openai.organization'),
                'OpenAI-Project' => config('services.openai.project'),
            ], static fn (?string $value): bool => filled($value)));
    }

    private function cacheKey(): string
    {
        return 'openai-platform-dashboard:'.sha1(json_encode([
            'standard' => $this->standardApiKey(),
            'usage' => $this->usageApiKey(),
            'organization' => config('services.openai.organization'),
            'project' => config('services.openai.project'),
        ]));
    }
}
