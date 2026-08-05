<?php

namespace App\Crm\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleCloudPlatformService
{
    private ?string $accessToken = null;

    /**
     * @return array{
     *     hasPlacesApiKey: bool,
     *     hasCloudCredentials: bool,
     *     hasOAuthClientConfig: bool,
     *     hasBillingAccountId: bool,
     *     connectionLabel: string,
     *     project: array{id: ?string, has_oauth_client_config: bool},
     *     billing: array{status: string, billing_enabled: ?bool, billing_account_name: ?string, message: string},
     *     enabledMapsServices: array{status: string, message: string, items: array<int, array{name: string, title: string}>},
     *     budgets: array{status: string, message: string, items: array<int, array{display_name: string, amount: string, thresholds: string}>},
     *     requests: array{status: string, total: ?int, message: string, range_label: string, daily: array<int, array{label: string, value: int}>},
     *     errorMetrics: array{status: string, total: ?int, message: string, range_label: string, daily: array<int, array{label: string, value: int}>},
     *     latency: array{status: string, median_ms: ?float, message: string, range_label: string, daily: array<int, array{label: string, value: float}>}
     * }
     */
    public function getDashboardData(): array
    {
        $hasPlacesApiKey = filled(config('services.google.places_api_key'));
        $hasOAuthClientConfig = filled($this->oauthClientId()) && filled($this->oauthClientSecret());
        $hasCloudCredentials = filled($this->projectId()) && $hasOAuthClientConfig && filled($this->oauthRefreshToken());
        $hasBillingAccountId = filled($this->billingAccountId());

        if (! filled($this->projectId()) || ! $hasOAuthClientConfig) {
            return [
                'hasPlacesApiKey' => $hasPlacesApiKey,
                'hasCloudCredentials' => false,
                'hasOAuthClientConfig' => $hasOAuthClientConfig,
                'hasBillingAccountId' => $hasBillingAccountId,
                'connectionLabel' => 'Setup required',
                'project' => [
                    'id' => $this->projectId(),
                    'has_oauth_client_config' => $hasOAuthClientConfig,
                ],
                'billing' => [
                    'status' => 'missing_credentials',
                    'billing_enabled' => null,
                    'billing_account_name' => null,
                    'message' => 'This CRM needs GOOGLE_CLOUD_PROJECT_ID, GOOGLE_CLOUD_OAUTH_CLIENT_ID, and GOOGLE_CLOUD_OAUTH_CLIENT_SECRET configured before you can connect with Google.',
                ],
                'enabledMapsServices' => [
                    'status' => 'missing_credentials',
                    'message' => 'Finish the Google app configuration in the environment before loading Maps services.',
                    'items' => [],
                ],
                'budgets' => [
                    'status' => 'missing_credentials',
                    'message' => 'Finish the Google app configuration in the environment before loading budgets.',
                    'items' => [],
                ],
                'requests' => $this->emptyCountSeries('Finish the Google app configuration in the environment before loading request volume.'),
                'errorMetrics' => $this->emptyCountSeries('Finish the Google app configuration in the environment before loading error volume.'),
                'latency' => $this->emptyLatencySeries('Finish the Google app configuration in the environment before loading median latency.'),
            ];
        }

        if (! $hasCloudCredentials) {
            return [
                'hasPlacesApiKey' => $hasPlacesApiKey,
                'hasCloudCredentials' => false,
                'hasOAuthClientConfig' => true,
                'hasBillingAccountId' => $hasBillingAccountId,
                'connectionLabel' => 'Authorization required',
                'project' => [
                    'id' => $this->projectId(),
                    'has_oauth_client_config' => true,
                ],
                'billing' => [
                    'status' => 'missing_credentials',
                    'billing_enabled' => null,
                    'billing_account_name' => null,
                    'message' => 'Authorize this CRM with Google to load billing and usage data.',
                ],
                'enabledMapsServices' => [
                    'status' => 'missing_credentials',
                    'message' => 'Authorize this CRM with Google to inspect enabled Maps services.',
                    'items' => [],
                ],
                'budgets' => [
                    'status' => 'missing_credentials',
                    'message' => 'Authorize this CRM with Google to inspect budgets.',
                    'items' => [],
                ],
                'requests' => $this->emptyCountSeries('Authorize this CRM with Google to load request volume.'),
                'errorMetrics' => $this->emptyCountSeries('Authorize this CRM with Google to load error volume.'),
                'latency' => $this->emptyLatencySeries('Authorize this CRM with Google to load median latency.'),
            ];
        }

        return Cache::remember(
            $this->cacheKey(),
            now()->addHour(),
            function () use ($hasPlacesApiKey, $hasCloudCredentials, $hasOAuthClientConfig, $hasBillingAccountId): array {
                return [
                    'hasPlacesApiKey' => $hasPlacesApiKey,
                    'hasCloudCredentials' => $hasCloudCredentials,
                    'hasOAuthClientConfig' => $hasOAuthClientConfig,
                    'hasBillingAccountId' => $hasBillingAccountId,
                    'connectionLabel' => 'Connected',
                    'project' => [
                        'id' => $this->projectId(),
                        'has_oauth_client_config' => $hasOAuthClientConfig,
                    ],
                    'billing' => $this->fetchBillingInfo(),
                    'enabledMapsServices' => $this->fetchEnabledMapsServices(),
                    'budgets' => $this->fetchBudgets(),
                    'requests' => $this->fetchRequestSeries(),
                    'errorMetrics' => $this->fetchErrorSeries(),
                    'latency' => $this->fetchLatencySeries(),
                ];
            }
        );
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array{status: string, billing_enabled: ?bool, billing_account_name: ?string, message: string}
     */
    private function fetchBillingInfo(): array
    {
        $response = $this->googleRequest(
            'https://cloudbilling.googleapis.com/v1/projects/'.$this->projectId().'/billingInfo'
        );

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'billing_enabled' => null,
                'billing_account_name' => null,
                'message' => $this->extractGoogleError($response, 'Billing info could not be loaded.'),
            ];
        }

        return [
            'status' => 'available',
            'billing_enabled' => (bool) $response->json('billingEnabled'),
            'billing_account_name' => $response->json('billingAccountName'),
            'message' => $response->json('billingEnabled')
                ? 'Billing is enabled for this Google Cloud project.'
                : 'Billing is not enabled for this Google Cloud project.',
        ];
    }

    /**
     * @return array{status: string, message: string, items: array<int, array{name: string, title: string}>}
     */
    private function fetchEnabledMapsServices(): array
    {
        $response = $this->googleRequest(
            'https://serviceusage.googleapis.com/v1/projects/'.$this->projectId().'/services',
            [
                'filter' => 'state:ENABLED',
                'pageSize' => 200,
            ]
        );

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => $this->extractGoogleError($response, 'Enabled services could not be loaded.'),
                'items' => [],
            ];
        }

        $items = collect($response->json('services', []))
            ->filter(fn (array $service): bool => $this->isRelevantMapsService($service))
            ->map(function (array $service): array {
                return [
                    'name' => Str::after((string) data_get($service, 'name'), 'services/'),
                    'title' => (string) data_get($service, 'config.title', Str::after((string) data_get($service, 'name'), 'services/')),
                ];
            })
            ->values()
            ->all();

        return [
            'status' => 'available',
            'message' => empty($items)
                ? 'No Google Maps related services were detected as enabled for this project.'
                : 'Enabled Google Maps related services for this project.',
            'items' => $items,
        ];
    }

    /**
     * @return array{status: string, message: string, items: array<int, array{display_name: string, amount: string, thresholds: string}>}
     */
    private function fetchBudgets(): array
    {
        if (! filled($this->billingAccountId())) {
            return [
                'status' => 'missing_config',
                'message' => 'Add a billing account ID to load Google Cloud budgets.',
                'items' => [],
            ];
        }

        $response = $this->googleRequest(
            'https://billingbudgets.googleapis.com/v1/billingAccounts/'.$this->billingAccountId().'/budgets',
            ['pageSize' => 50]
        );

        if (! $response->ok()) {
            return [
                'status' => 'error',
                'message' => $this->extractGoogleError($response, 'Budgets could not be loaded.'),
                'items' => [],
            ];
        }

        $items = collect($response->json('budgets', []))
            ->map(function (array $budget): array {
                $thresholds = collect($budget['thresholdRules'] ?? [])
                    ->map(function (array $rule): string {
                        $value = (float) ($rule['thresholdPercent'] ?? 0);

                        return number_format($value * 100, 0).'%';
                    })
                    ->implode(', ');

                return [
                    'display_name' => (string) ($budget['displayName'] ?? 'Unnamed budget'),
                    'amount' => $this->formatBudgetAmount($budget),
                    'thresholds' => $thresholds !== '' ? $thresholds : 'No thresholds',
                ];
            })
            ->values()
            ->all();

        return [
            'status' => 'available',
            'message' => empty($items)
                ? 'No budgets were returned for this billing account.'
                : 'Budgets linked to the configured billing account.',
            'items' => $items,
        ];
    }

    /**
     * @return array{status: string, total: ?int, message: string, range_label: string, daily: array<int, array{label: string, value: int}>}
     */
    private function fetchRequestSeries(): array
    {
        $range = $this->currentRange();
        $response = $this->googleRequest(
            'https://monitoring.googleapis.com/v3/projects/'.$this->projectId().'/timeSeries',
            [
                'filter' => 'metric.type="maps.googleapis.com/service/v2/request_count" AND resource.type="maps.googleapis.com/Api"',
                'interval.startTime' => $range['start']->toIso8601String(),
                'interval.endTime' => $range['end']->toIso8601String(),
                'aggregation.alignmentPeriod' => '86400s',
                'aggregation.perSeriesAligner' => 'ALIGN_SUM',
                'view' => 'FULL',
            ]
        );

        if (! $response->ok()) {
            return $this->emptyCountSeries($this->extractGoogleError($response, 'Request volume could not be loaded.'));
        }

        $daily = $this->aggregateIntSeries($response->json('timeSeries', []));

        return [
            'status' => 'available',
            'total' => array_sum(array_column($daily, 'value')),
            'message' => 'Daily Google Maps request count from Cloud Monitoring.',
            'range_label' => $range['label'],
            'daily' => $daily,
        ];
    }

    /**
     * @return array{status: string, total: ?int, message: string, range_label: string, daily: array<int, array{label: string, value: int}>}
     */
    private function fetchErrorSeries(): array
    {
        $range = $this->currentRange();
        $fourXxResponse = $this->googleRequest(
            'https://monitoring.googleapis.com/v3/projects/'.$this->projectId().'/timeSeries',
            [
                'filter' => 'metric.type="maps.googleapis.com/service/v2/request_count" AND resource.type="maps.googleapis.com/Api" AND metric.labels.response_code_class="4xx"',
                'interval.startTime' => $range['start']->toIso8601String(),
                'interval.endTime' => $range['end']->toIso8601String(),
                'aggregation.alignmentPeriod' => '86400s',
                'aggregation.perSeriesAligner' => 'ALIGN_SUM',
                'view' => 'FULL',
            ]
        );
        $fiveXxResponse = $this->googleRequest(
            'https://monitoring.googleapis.com/v3/projects/'.$this->projectId().'/timeSeries',
            [
                'filter' => 'metric.type="maps.googleapis.com/service/v2/request_count" AND resource.type="maps.googleapis.com/Api" AND metric.labels.response_code_class="5xx"',
                'interval.startTime' => $range['start']->toIso8601String(),
                'interval.endTime' => $range['end']->toIso8601String(),
                'aggregation.alignmentPeriod' => '86400s',
                'aggregation.perSeriesAligner' => 'ALIGN_SUM',
                'view' => 'FULL',
            ]
        );

        if (! $fourXxResponse->ok()) {
            return $this->emptyCountSeries($this->extractGoogleError($fourXxResponse, 'Error volume could not be loaded.'));
        }

        if (! $fiveXxResponse->ok()) {
            return $this->emptyCountSeries($this->extractGoogleError($fiveXxResponse, 'Error volume could not be loaded.'));
        }

        $daily = $this->mergeSeries(
            $this->aggregateIntSeries($fourXxResponse->json('timeSeries', [])),
            $this->aggregateIntSeries($fiveXxResponse->json('timeSeries', []))
        );

        return [
            'status' => 'available',
            'total' => array_sum(array_column($daily, 'value')),
            'message' => 'Daily Google Maps 4xx and 5xx request count from Cloud Monitoring.',
            'range_label' => $range['label'],
            'daily' => $daily,
        ];
    }

    /**
     * @return array{status: string, median_ms: ?float, message: string, range_label: string, daily: array<int, array{label: string, value: float}>}
     */
    private function fetchLatencySeries(): array
    {
        $range = $this->currentRange();
        $response = $this->googleRequest(
            'https://monitoring.googleapis.com/v3/projects/'.$this->projectId().'/timeSeries',
            [
                'filter' => 'metric.type="maps.googleapis.com/service/v2/request_latencies" AND resource.type="maps.googleapis.com/Api"',
                'interval.startTime' => $range['start']->toIso8601String(),
                'interval.endTime' => $range['end']->toIso8601String(),
                'aggregation.alignmentPeriod' => '86400s',
                'aggregation.perSeriesAligner' => 'ALIGN_PERCENTILE_50',
                'view' => 'FULL',
            ]
        );

        if (! $response->ok()) {
            return $this->emptyLatencySeries($this->extractGoogleError($response, 'Latency could not be loaded.'));
        }

        $daily = $this->aggregateFloatSeries($response->json('timeSeries', []));
        $median = empty($daily) ? null : round(collect($daily)->avg('value'), 2);

        return [
            'status' => 'available',
            'median_ms' => $median,
            'message' => 'Daily median latency from Google Maps Platform monitoring.',
            'range_label' => $range['label'],
            'daily' => $daily,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $timeSeries
     * @return array<int, array{label: string, value: int}>
     */
    private function aggregateIntSeries(array $timeSeries): array
    {
        $totals = [];

        foreach ($timeSeries as $series) {
            foreach ($series['points'] ?? [] as $point) {
                $dateKey = Carbon::parse((string) data_get($point, 'interval.endTime'))->subDay()->format('Y-m-d');
                $value = (int) data_get($point, 'value.int64Value', 0);
                $totals[$dateKey] = ($totals[$dateKey] ?? 0) + $value;
            }
        }

        ksort($totals);

        return collect($totals)
            ->map(fn (int $value, string $date): array => [
                'label' => Carbon::parse($date)->format('M d'),
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $timeSeries
     * @return array<int, array{label: string, value: float}>
     */
    private function aggregateFloatSeries(array $timeSeries): array
    {
        $totals = [];
        $counts = [];

        foreach ($timeSeries as $series) {
            foreach ($series['points'] ?? [] as $point) {
                $dateKey = Carbon::parse((string) data_get($point, 'interval.endTime'))->subDay()->format('Y-m-d');
                $value = (float) data_get($point, 'value.doubleValue', 0);
                $totals[$dateKey] = ($totals[$dateKey] ?? 0) + $value;
                $counts[$dateKey] = ($counts[$dateKey] ?? 0) + 1;
            }
        }

        ksort($totals);

        return collect(array_keys($totals))
            ->map(function (string $date) use ($counts, $totals): array {
                $average = $counts[$date] > 0
                    ? round($totals[$date] / $counts[$date], 2)
                    : 0.0;

                return [
                    'label' => Carbon::parse($date)->format('M d'),
                    'value' => $average,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{label: string, value: int}>  $first
     * @param  array<int, array{label: string, value: int}>  $second
     * @return array<int, array{label: string, value: int}>
     */
    private function mergeSeries(array $first, array $second): array
    {
        $merged = [];

        foreach ([$first, $second] as $series) {
            foreach ($series as $point) {
                $merged[$point['label']] = ($merged[$point['label']] ?? 0) + $point['value'];
            }
        }

        return collect($merged)
            ->map(fn (int $value, string $label): array => ['label' => $label, 'value' => $value])
            ->values()
            ->all();
    }

    /**
     * @return array{status: string, total: ?int, message: string, range_label: string, daily: array<int, array{label: string, value: int}>}
     */
    private function emptyCountSeries(string $message): array
    {
        return [
            'status' => 'error',
            'total' => null,
            'message' => $message,
            'range_label' => $this->currentRange()['label'],
            'daily' => [],
        ];
    }

    /**
     * @return array{status: string, median_ms: ?float, message: string, range_label: string, daily: array<int, array{label: string, value: float}>}
     */
    private function emptyLatencySeries(string $message): array
    {
        return [
            'status' => 'error',
            'median_ms' => null,
            'message' => $message,
            'range_label' => $this->currentRange()['label'],
            'daily' => [],
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    private function currentRange(): array
    {
        $start = now()->subDays(14)->startOfDay();
        $end = now();

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('F j, Y').' to '.$end->format('F j, Y'),
        ];
    }

    private function projectId(): ?string
    {
        return config('services.google.cloud_project_id');
    }

    private function billingAccountId(): ?string
    {
        return config('services.google.cloud_billing_account_id');
    }

    private function googleRequest(string $url, array $query = [])
    {
        return Http::withToken($this->fetchAccessToken())
            ->acceptJson()
            ->timeout(20)
            ->get($url, $query);
    }

    private function fetchAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->oauthClientId(),
                'client_secret' => $this->oauthClientSecret(),
                'refresh_token' => $this->oauthRefreshToken(),
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->ok()) {
            throw new \RuntimeException($this->extractGoogleError($response, 'Google OAuth token request failed.'));
        }

        $this->accessToken = (string) $response->json('access_token');

        if ($this->accessToken === '') {
            throw new \RuntimeException('Google OAuth token response did not include an access token.');
        }

        return $this->accessToken;
    }

    private function cacheKey(): string
    {
        return 'google-cloud-platform-dashboard:'.sha1(json_encode([
            'project' => $this->projectId(),
            'billing_account' => $this->billingAccountId(),
            'oauth_client_id' => $this->oauthClientId(),
            'oauth_refresh_token' => $this->oauthRefreshToken(),
        ]));
    }

    private function oauthClientId(): ?string
    {
        return config('services.google.oauth_client_id');
    }

    private function oauthClientSecret(): ?string
    {
        return config('services.google.oauth_client_secret');
    }

    private function oauthRefreshToken(): ?string
    {
        return config('services.google.oauth_refresh_token');
    }

    private function extractGoogleError($response, string $fallback): string
    {
        $message = data_get($response->json(), 'error.message');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $service
     */
    private function isRelevantMapsService(array $service): bool
    {
        $name = Str::lower((string) data_get($service, 'name'));
        $title = Str::lower((string) data_get($service, 'config.title'));

        return Str::contains($name, [
            'maps',
            'places',
            'routes',
            'geocoding',
            'timezone',
            'elevation',
            'geolocation',
            'streetview',
        ]) || Str::contains($title, 'maps');
    }

    /**
     * @param  array<string, mixed>  $budget
     */
    private function formatBudgetAmount(array $budget): string
    {
        $units = (float) ($budget['amount']['specifiedAmount']['units'] ?? 0);
        $nanos = (float) ($budget['amount']['specifiedAmount']['nanos'] ?? 0);
        $currency = (string) ($budget['amount']['specifiedAmount']['currencyCode'] ?? 'USD');

        if ($units === 0.0 && $nanos === 0.0) {
            return 'Custom amount';
        }

        $value = $units + ($nanos / 1_000_000_000);

        return strtoupper($currency).' '.number_format($value, 2);
    }
}
