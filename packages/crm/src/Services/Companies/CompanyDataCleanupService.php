<?php

namespace WilliamBeeler\Crm\Services\Companies;

use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use WilliamBeeler\Crm\Models\Company;

class CompanyDataCleanupService
{
    private const CLEANUP_PROMPT_PATH = 'prompts/company-cleanup.md';

    private const CLEANUP_FIELDS = [
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'email',
        'phone',
    ];

    /**
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function cleanupCompany(
        Company $company,
        bool $persist = true,
        string $provider = 'openai',
        string $model = 'gpt-4.1-mini'
    ): array {
        $original = [
            'website' => $company->website,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
            'email' => $company->email,
            'phone' => $company->phone,
        ];

        $aiSuggested = $this->requestCleanupSuggestions($company, $provider, $model);
        $cleaned = [];
        foreach (self::CLEANUP_FIELDS as $field) {
            $cleaned[$field] = $this->normalizeNullableString($aiSuggested[$field] ?? $original[$field] ?? null);
        }

        $cleaned['website'] = $this->normalizeWebsite($cleaned['website']);
        $cleaned['country'] = $this->normalizeCountry($cleaned['country']);

        if (! empty($cleaned['city'])) {
            $cleaned = $this->extractCountryFromCity($cleaned);
        }

        if (! empty($cleaned['state'])) {
            $cleaned = $this->splitCombinedStatePostal($cleaned);
        }

        if (! empty($cleaned['address_line1'])) {
            $parsed = $this->parseFullAddress($cleaned['address_line1']);
            if ($parsed !== null) {
                $cleaned['address_line1'] = $parsed['address_line1'];
                $cleaned['city'] = $parsed['city'];
                $cleaned['state'] = $parsed['state'];
                if (! empty($parsed['postal_code'])) {
                    $cleaned['postal_code'] = $parsed['postal_code'];
                }
                if (! empty($parsed['country'])) {
                    $cleaned['country'] = $this->normalizeCountry($parsed['country']);
                }
            }
        }

        if (! empty($cleaned['state']) && strlen((string) $cleaned['state']) <= 3) {
            $cleaned['state'] = strtoupper((string) $cleaned['state']);
        }
        $cleaned['country'] = $this->normalizeCountry($cleaned['country']);

        $changes = [];
        foreach ($cleaned as $field => $value) {
            if ($value !== $original[$field]) {
                $changes[$field] = [
                    'from' => $original[$field],
                    'to' => $value,
                ];
            }
        }

        if ($persist && ! empty($changes)) {
            $company->fill(array_map(static fn (array $change) => $change['to'], $changes));

            $meta = is_array($company->meta) ? $company->meta : [];
            $cleanupPrompt = $this->loadCleanupPromptMarkdown();
            $meta['cleanup'] = [
                'ran_at' => now()->toIso8601String(),
                'prompt_path' => self::CLEANUP_PROMPT_PATH,
                'prompt_sha256' => hash('sha256', $cleanupPrompt),
                'changed_fields' => array_keys($changes),
                'provider' => $provider,
                'model' => $model,
            ];
            $company->meta = $meta;

            $company->save();
        }

        return $changes;
    }

    public function normalizeCountry(?string $country): ?string
    {
        $normalized = $this->normalizeNullableString($country);
        if ($normalized === null) {
            return null;
        }

        $key = strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '', $normalized));
        $usaVariants = [
            'US',
            'USA',
            'UNITEDSTATES',
            'UNITEDSTATESOFAMERICA',
            'U S',
            'U S A',
        ];

        if (in_array($key, $usaVariants, true)) {
            return 'USA';
        }

        return $normalized;
    }

    private function extractCountryFromCity(array $cleaned): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $cleaned['city']))));
        if (count($parts) < 2) {
            return $cleaned;
        }

        $candidateCountry = $this->normalizeCountry($parts[count($parts) - 1]);
        if ($candidateCountry !== 'USA') {
            return $cleaned;
        }

        array_pop($parts);
        $cleaned['city'] = implode(', ', $parts);
        if (empty($cleaned['country'])) {
            $cleaned['country'] = 'USA';
        }

        return $cleaned;
    }

    private function splitCombinedStatePostal(array $cleaned): array
    {
        $state = (string) $cleaned['state'];
        if (! preg_match('/^\s*([A-Za-z]{2})\s+([A-Za-z0-9\-]+)\s*$/', $state, $matches)) {
            return $cleaned;
        }

        $cleaned['state'] = strtoupper($matches[1]);
        if (empty($cleaned['postal_code'])) {
            $cleaned['postal_code'] = trim($matches[2]);
        }

        return $cleaned;
    }

    /**
     * @return array{address_line1: string, city: string, state: string, postal_code: ?string, country: ?string}|null
     */
    private function parseFullAddress(string $addressLine1): ?array
    {
        $addressLine1 = trim($addressLine1);
        if (substr_count($addressLine1, ',') < 2) {
            return null;
        }

        $pattern = '/^\s*(?<street>.+?),\s*(?<city>[^,]+?),\s*(?<state>[A-Za-z]{2})(?:\s+(?<postal>[A-Za-z0-9\-]+))?(?:,\s*(?<country>[^,]+))?\s*$/';
        if (! preg_match($pattern, $addressLine1, $matches)) {
            return null;
        }

        $street = trim((string) ($matches['street'] ?? ''));
        $city = trim((string) ($matches['city'] ?? ''));
        $state = strtoupper(trim((string) ($matches['state'] ?? '')));
        if ($street === '' || $city === '' || $state === '') {
            return null;
        }

        $postalCode = $this->normalizeNullableString((string) ($matches['postal'] ?? ''));
        $country = $this->normalizeNullableString((string) ($matches['country'] ?? ''));

        return [
            'address_line1' => $street,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'country' => $country,
        ];
    }

    private function normalizeWebsite(?string $website): ?string
    {
        $website = $this->normalizeNullableString($website);
        if ($website === null) {
            return null;
        }

        if (! preg_match('#^https?://#i', $website)) {
            $website = 'https://'.ltrim($website, '/');
        }

        return filter_var($website, FILTER_VALIDATE_URL) ? $website : $this->normalizeNullableString($website);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');

        return $normalized === '' ? null : $normalized;
    }

    private function loadCleanupPromptMarkdown(): string
    {
        $path = dirname(__DIR__, 3).'/resources/'.self::CLEANUP_PROMPT_PATH;
        if (! is_file($path)) {
            return <<<'MD'
# Company Cleanup Rules (Fallback)
- Trim whitespace and normalize empty strings to null.
- Normalize website URL format.
- Normalize USA country variants to "USA".
- Parse full address in address_line1 when possible.
- Split combined state/postal patterns.
MD;
        }

        $content = trim((string) file_get_contents($path));
        if ($content === '') {
            throw new \RuntimeException('Cleanup prompt file is empty: '.self::CLEANUP_PROMPT_PATH);
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestCleanupSuggestions(Company $company, string $provider, string $model): array
    {
        $fields = array_map(
            static fn (string $field): StringSchema => new StringSchema($field, "Normalized value for {$field}.", true),
            self::CLEANUP_FIELDS
        );

        $schema = new ObjectSchema(
            'company_cleanup',
            'Normalized company data cleanup output.',
            $fields,
            requiredFields: self::CLEANUP_FIELDS
        );

        $companyJson = json_encode([
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'address_line1' => $company->address_line1,
            'address_line2' => $company->address_line2,
            'city' => $company->city,
            'state' => $company->state,
            'postal_code' => $company->postal_code,
            'country' => $company->country,
            'email' => $company->email,
            'phone' => $company->phone,
        ], JSON_PRETTY_PRINT);

        $promptTemplate = $this->loadCleanupPromptMarkdown();
        $prompt = strtr($promptTemplate, [
            '{{company_json}}' => (string) $companyJson,
        ]);

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt('You are a strict CRM data normalization assistant. Return only schema-compliant values.')
            ->withSchema($schema)
            ->withPrompt($prompt)
            ->asStructured();

        return is_array($response->structured) ? $response->structured : [];
    }
}
