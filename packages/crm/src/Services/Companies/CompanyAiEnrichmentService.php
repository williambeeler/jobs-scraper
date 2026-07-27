<?php

namespace WilliamBeeler\Crm\Services\Companies;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use WilliamBeeler\Crm\Models\Company;

class CompanyAiEnrichmentService
{
    private const GOOGLE_PLACES_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch/json';

    private const GOOGLE_PLACES_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

    /**
     * @var array<string, array{input_per_million: float, output_per_million: float}>
     */
    private const OPENAI_MODEL_PRICING = [
        'gpt-4.1-mini' => [
            'input_per_million' => 0.40,
            'output_per_million' => 1.60,
        ],
    ];

    private const ENRICHMENT_FIELDS = [
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'email',
        'phone',
        'notes',
    ];

    private const ENRICHMENT_PROMPT_PATH = 'prompts/company-enrichment.md';

    public function __construct(
        private readonly CompanyDataCleanupService $cleanupService
    ) {}

    /**
     * @return array{
     *   suggested: array<string, mixed>,
     *   applied: array<string, array{from: mixed, to: mixed}>,
     *   cleanup: array<string, array{from: mixed, to: mixed}>,
     *   website_candidates: array<int, string>,
     *   usage: array<string, int|null>,
     *   cost_estimate_usd: float|null
     * }
     */
    public function enrichCompany(
        Company $company,
        string $provider,
        string $model,
        ?string $manualWebsiteCandidate = null,
        bool $overwrite = false,
        bool $persist = true
    ): array {
        $websiteCandidates = $this->discoverWebsiteCandidates($company);
        $manualCandidate = $this->normalizeCandidateUrl((string) $manualWebsiteCandidate);
        if ($manualCandidate !== null) {
            array_unshift($websiteCandidates, $manualCandidate);
            $websiteCandidates = array_values(array_unique($websiteCandidates));
        }

        $suggestionResult = $this->requestSuggestions($company, $provider, $model, $websiteCandidates);
        $suggested = $this->normalizeSuggestedPayload($suggestionResult['structured']);
        $usage = $suggestionResult['usage'];
        $costEstimateUsd = $this->estimateCostUsd(
            $provider,
            $model,
            (int) ($usage['prompt_tokens'] ?? 0),
            (int) ($usage['completion_tokens'] ?? 0)
        );

        if (empty($suggested['website']) && ! empty($websiteCandidates)) {
            $suggested['website'] = $websiteCandidates[0];
        }

        $fields = array_values(array_filter(self::ENRICHMENT_FIELDS, static fn (string $field): bool => $field !== 'notes'));

        $applied = [];
        foreach ($fields as $field) {
            $rawValue = $suggested[$field] ?? null;
            $suggestedValue = is_string($rawValue) ? trim($rawValue) : null;
            if ($suggestedValue === '') {
                $suggestedValue = null;
            }

            if ($field === 'country') {
                $suggestedValue = $this->cleanupService->normalizeCountry($suggestedValue);
            }

            if ($suggestedValue === null) {
                continue;
            }

            $currentValue = $company->{$field};
            $currentFilled = is_string($currentValue)
                ? trim($currentValue) !== ''
                : ! is_null($currentValue);

            if (! $overwrite && $currentFilled) {
                continue;
            }

            if ($currentValue === $suggestedValue) {
                continue;
            }

            $applied[$field] = [
                'from' => $currentValue,
                'to' => $suggestedValue,
            ];
        }

        if ($persist && ! empty($applied)) {
            $company->fill(array_map(static fn (array $change) => $change['to'], $applied));

            $meta = is_array($company->meta) ? $company->meta : [];
            $meta['ai_enrichment'] = [
                'provider' => $provider,
                'model' => $model,
                'ran_at' => now()->toIso8601String(),
                'notes' => $suggested['notes'] ?? null,
                'website_candidates' => $websiteCandidates,
                'usage' => $usage,
                'cost_estimate_usd' => $costEstimateUsd,
            ];

            $company->meta = $meta;
            $company->save();
        }

        $cleanupChanges = $this->cleanupService->cleanupCompany($company, $persist, $provider, $model);

        return [
            'suggested' => $suggested,
            'applied' => $applied,
            'cleanup' => $cleanupChanges,
            'website_candidates' => $websiteCandidates,
            'usage' => $usage,
            'cost_estimate_usd' => $costEstimateUsd,
        ];
    }

    /**
     * @return array{
     *   structured: array<string, mixed>,
     *   usage: array<string, int|null>
     * }
     */
    private function requestSuggestions(Company $company, string $provider, string $model, array $websiteCandidates = []): array
    {
        $properties = [
            new StringSchema('website', 'Canonical website URL. Prefer official domain, fallback to Facebook page or equivalent if no official site exists.', true),
            new StringSchema('address_line1', 'Street address only (no city/state/country), if known.', true),
            new StringSchema('address_line2', 'Address line 2 (suite/unit), if known.', true),
            new StringSchema('city', 'City name.', true),
            new StringSchema('state', 'State or province (2-letter abbreviation when applicable).', true),
            new StringSchema('postal_code', 'Postal code / ZIP.', true),
            new StringSchema('country', 'Country name, use USA for United States.', true),
            new StringSchema('email', 'Public contact email if confidently known.', true),
            new StringSchema('phone', 'Public contact phone if confidently known.', true),
            new StringSchema('notes', 'Short note about what was found and confidence.', true),
        ];

        $schema = new ObjectSchema(
            'company_enrichment',
            'Suggested normalized company data for CRM.',
            $properties,
            requiredFields: array_map(static fn (StringSchema $schema): string => $schema->name(), $properties)
        );

        $companyJson = json_encode([
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'email' => $company->email,
            'phone' => $company->phone,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
        ], JSON_PRETTY_PRINT);

        $promptTemplate = $this->loadEnrichmentPromptTemplate();
        $prompt = strtr($promptTemplate, [
            '{{website_candidates}}' => json_encode($websiteCandidates, JSON_PRETTY_PRINT),
            '{{company_json}}' => (string) $companyJson,
        ]);

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt('You are a meticulous CRM data assistant. Return strictly structured output matching the schema.')
            ->withSchema($schema)
            ->withPrompt($prompt)
            ->asStructured();

        return [
            'structured' => is_array($response->structured) ? $response->structured : [],
            'usage' => [
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'cache_write_input_tokens' => $response->usage->cacheWriteInputTokens,
                'cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
                'thought_tokens' => $response->usage->thoughtTokens,
            ],
        ];
    }

    private function loadEnrichmentPromptTemplate(): string
    {
        $path = dirname(__DIR__, 3).'/resources/'.self::ENRICHMENT_PROMPT_PATH;
        if (! is_file($path)) {
            return <<<'PROMPT'
Enrich this company record. Return ONLY fields you can infer confidently.
Rules:
- PRIORITY ORDER: website, phone, address.
- Always include website and phone keys in output (set null when unknown).
- Prefer the official main website when available.
- If no official website exists, use a Facebook page or equivalent site (like Wix) as website.
- If website candidates are provided, choose the best official website from them when possible.
- If an address appears as a single full line, split it into address_line1/city/state/postal_code/country.
- Normalize United States country values to USA.
- Leave unknown fields null.

Website candidates (search-ranked):
{{website_candidates}}

Company JSON:
{{company_json}}
PROMPT;
        }

        $template = trim((string) file_get_contents($path));
        if ($template === '') {
            throw new \RuntimeException('Enrichment prompt file is empty: '.self::ENRICHMENT_PROMPT_PATH);
        }

        return $template;
    }

    private function estimateCostUsd(string $provider, string $model, int $promptTokens, int $completionTokens): ?float
    {
        if (strtolower($provider) !== 'openai') {
            return null;
        }

        $pricing = self::OPENAI_MODEL_PRICING[$model] ?? null;
        if ($pricing === null) {
            return null;
        }

        $inputCost = ($promptTokens / 1_000_000) * $pricing['input_per_million'];
        $outputCost = ($completionTokens / 1_000_000) * $pricing['output_per_million'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * @return array<int, string>
     */
    private function discoverWebsiteCandidates(Company $company): array
    {
        if (app()->environment('testing')) {
            return [];
        }

        $googleCandidates = $this->discoverWebsiteCandidatesFromGooglePlaces($company);
        if (! empty($googleCandidates)) {
            return $googleCandidates;
        }

        $guessedCandidates = $this->discoverWebsiteCandidatesFromDomainGuessing($company);
        if (! empty($guessedCandidates)) {
            return $guessedCandidates;
        }

        $searchParts = array_filter([
            trim((string) $company->name),
            trim((string) $company->address_line1),
            trim((string) $company->address_line2),
            trim((string) $company->city),
            trim((string) $company->state),
            trim((string) $company->postal_code),
            trim((string) $company->country),
            'official website',
        ]);

        $query = trim(implode(' ', $searchParts));
        if ($query === '') {
            return [];
        }

        $rawLinks = [];
        foreach ([
            'https://duckduckgo.com/html/',
            'https://lite.duckduckgo.com/lite/',
        ] as $url) {
            try {
                $response = Http::timeout(10)
                    ->retry(1, 200)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; CRMEnrichmentBot/1.0)',
                    ])
                    ->get($url, ['q' => $query]);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->ok()) {
                continue;
            }

            $rawLinks = array_merge($rawLinks, $this->extractLinksFromSearchHtml($response->body()));
        }

        if (empty($rawLinks)) {
            return [];
        }

        $urls = [];
        foreach ($rawLinks as $rawLink) {
            $decoded = html_entity_decode($rawLink, ENT_QUOTES | ENT_HTML5);
            $resolved = $this->resolveDuckDuckGoRedirect($decoded);
            $normalized = $this->normalizeCandidateUrl($resolved);

            if ($normalized === null) {
                continue;
            }

            $host = strtolower((string) parse_url($normalized, PHP_URL_HOST));
            if ($this->isDirectoryOrAggregatorHost($host)) {
                continue;
            }

            $urls[] = $normalized;
        }

        $urls = array_values(array_unique($urls));
        usort($urls, function (string $a, string $b): int {
            return $this->candidateScore($b) <=> $this->candidateScore($a);
        });

        return array_slice($urls, 0, 5);
    }

    /**
     * @return array<int, string>
     */
    private function discoverWebsiteCandidatesFromGooglePlaces(Company $company): array
    {
        $apiKey = (string) config('services.google.places_api_key', '');
        if ($apiKey === '') {
            return [];
        }

        $queryParts = array_filter([
            trim((string) $company->name),
            trim((string) $company->address_line1),
            trim((string) $company->address_line2),
            trim((string) $company->city),
            trim((string) $company->state),
            trim((string) $company->postal_code),
            trim((string) $company->country),
        ]);
        $query = trim(implode(' ', $queryParts));
        if ($query === '') {
            return [];
        }

        try {
            $searchResponse = Http::timeout(10)
                ->retry(1, 200)
                ->get(self::GOOGLE_PLACES_SEARCH_URL, [
                    'query' => $query,
                    'key' => $apiKey,
                ]);
        } catch (\Throwable) {
            return [];
        }

        if (! $searchResponse->ok()) {
            return [];
        }

        $results = $searchResponse->json('results', []);
        if (! is_array($results) || empty($results)) {
            return [];
        }

        $candidates = [];
        foreach (array_slice($results, 0, 5) as $result) {
            $placeId = (string) ($result['place_id'] ?? '');
            if ($placeId === '') {
                continue;
            }

            try {
                $detailsResponse = Http::timeout(10)
                    ->retry(1, 200)
                    ->get(self::GOOGLE_PLACES_DETAILS_URL, [
                        'place_id' => $placeId,
                        'fields' => 'website',
                        'key' => $apiKey,
                    ]);
            } catch (\Throwable) {
                continue;
            }

            if (! $detailsResponse->ok()) {
                continue;
            }

            $website = (string) $detailsResponse->json('result.website', '');
            $normalized = $this->normalizeCandidateUrl($website);
            if ($normalized !== null) {
                $candidates[] = $normalized;
            }
        }

        $candidates = array_values(array_unique($candidates));
        usort($candidates, function (string $a, string $b): int {
            return $this->candidateScore($b) <=> $this->candidateScore($a);
        });

        return array_slice($candidates, 0, 5);
    }

    /**
     * @return array<int, string>
     */
    private function discoverWebsiteCandidatesFromDomainGuessing(Company $company): array
    {
        $name = trim((string) $company->name);
        if ($name === '') {
            return [];
        }

        $tokenized = strtolower((string) preg_replace('/[^a-z0-9]+/i', ' ', $name));
        $parts = array_values(array_filter(explode(' ', $tokenized)));
        if (empty($parts)) {
            return [];
        }

        $stopwords = ['the', 'and', 'of', 'for', 'inc', 'llc', 'co', 'company', 'corp', 'corporation'];
        $coreParts = array_values(array_filter($parts, static fn (string $part): bool => ! in_array($part, $stopwords, true)));
        if (empty($coreParts)) {
            $coreParts = $parts;
        }

        $baseCandidates = [];
        $baseCandidates[] = implode('', $parts);
        $baseCandidates[] = implode('', $coreParts);

        // Generate useful trimmed variants (helps names like "... church", "... ministry", etc.)
        if (count($coreParts) >= 3) {
            $baseCandidates[] = implode('', array_slice($coreParts, 0, count($coreParts) - 1));
        }
        if (count($coreParts) >= 2) {
            $baseCandidates[] = implode('', array_slice($coreParts, 0, 2));
        }
        if (count($parts) >= 2) {
            $baseCandidates[] = implode('', array_slice($parts, 0, 2));
        }

        $baseCandidates = array_values(array_unique(array_filter($baseCandidates)));
        $tlds = ['org', 'com', 'net', 'church'];
        $hosts = [];
        foreach (array_slice($baseCandidates, 0, 6) as $base) {
            foreach ($tlds as $tld) {
                $hosts[] = $base.'.'.$tld;
            }
        }

        $hosts = array_values(array_unique($hosts));
        $validated = [];

        foreach ($hosts as $host) {
            $url = 'https://'.$host;

            try {
                $response = Http::timeout(6)
                    ->retry(1, 200)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; CRMEnrichmentBot/1.0)',
                    ])
                    ->get($url);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->ok()) {
                continue;
            }

            $body = strtolower(substr((string) $response->body(), 0, 6000));
            $hits = 0;
            foreach ($coreParts as $part) {
                if (strlen($part) < 3) {
                    continue;
                }

                if (str_contains($body, strtolower($part))) {
                    $hits++;
                }
            }

            if ($hits === 0) {
                continue;
            }

            $normalized = $this->normalizeCandidateUrl((string) $response->effectiveUri() ?: $url);
            if ($normalized !== null) {
                $validated[] = $normalized;
            }
        }

        $validated = array_values(array_unique($validated));
        usort($validated, function (string $a, string $b): int {
            return $this->candidateScore($b) <=> $this->candidateScore($a);
        });

        return array_slice($validated, 0, 5);
    }

    /**
     * @return array<int, string>
     */
    private function extractLinksFromSearchHtml(string $html): array
    {
        $links = [];

        // Standard DDG HTML results.
        if (preg_match_all('/class=[\'"][^\'"]*result__a[^\'"]*[\'"][^>]*href=[\'"]([^\'"]+)[\'"]/i', $html, $matchesA)) {
            $links = array_merge($links, $matchesA[1]);
        }

        // DDG Lite often uses generic anchors in result rows.
        if (preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matchesGeneric)) {
            $links = array_merge($links, $matchesGeneric[1]);
        }

        return array_values(array_unique($links));
    }

    private function resolveDuckDuckGoRedirect(string $url): string
    {
        if (! str_contains($url, 'duckduckgo.com/l/?')) {
            return $url;
        }

        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        if (! empty($params['uddg']) && is_string($params['uddg'])) {
            return urldecode($params['uddg']);
        }

        return $url;
    }

    private function normalizeCandidateUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $scheme.'://'.$host.($path !== '' ? $path : '');
    }

    private function isDirectoryOrAggregatorHost(string $host): bool
    {
        $blocked = [
            'instagram.com',
            'linkedin.com',
            'yelp.com',
            'mapquest.com',
            'yellowpages.com',
            'tripadvisor.com',
            'bbb.org',
            'chamberofcommerce.com',
            'google.com',
            'maps.google.com',
            'bing.com',
            'duckduckgo.com',
        ];

        foreach ($blocked as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    private function candidateScore(string $url): int
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $score = 0;

        if (! str_contains($host, 'facebook.com') && ! str_contains($host, 'wixsite.com')) {
            $score += 10;
        }

        if (str_ends_with($host, '.org') || str_ends_with($host, '.com')) {
            $score += 3;
        }

        if (str_contains($host, 'wixsite.com')) {
            $score -= 2;
        }

        if (str_contains($host, 'facebook.com')) {
            $score -= 1;
        }

        return $score;
    }

    /**
     * @param  array<string, mixed>  $suggested
     * @return array<string, mixed>
     */
    private function normalizeSuggestedPayload(array $suggested): array
    {
        $normalized = [];

        foreach (self::ENRICHMENT_FIELDS as $field) {
            $value = $suggested[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }
}
