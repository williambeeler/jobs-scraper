<?php

namespace WilliamBeeler\Crm\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\GooglePlacesUsageLog;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Models\Tag;

class LeadScraperService
{
    protected string $googleApiKey;

    protected string $placesUrl = 'https://maps.googleapis.com/maps/api/place/textsearch/json';

    protected string $detailsUrl = 'https://maps.googleapis.com/maps/api/place/details/json';

    protected string $geocodeUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

    protected string $openCorporatesUrl = 'https://api.opencorporates.com/v0.4/companies/search';

    protected int $defaultRadiusInMiles = 10;

    protected int $defaultMaxCompanies = 20;

    protected int $maxRadiusInMeters = 50000;

    protected bool $enrich = true;

    protected int $googleRequestCount = 0;

    public function __construct()
    {
        $this->googleApiKey = (string) config('services.google.places_api_key', env('GOOGLE_PLACES_API_KEY'));
    }

    public function scrapeAndSave(
        $keywords,
        ?string $location = null,
        ?string $geo = null,
        ?string $tier = null,
        ?int $radiusInMiles = null,
        ?int $maxCompanies = null
    ): int {
        $totalCount = 0;
        $this->googleRequestCount = 0;
        $resolvedTier = GooglePlacesUsageLog::normalizeTier($tier);
        $searchOrigin = $this->resolveSearchOrigin($location, $geo);
        $resolvedRadiusInMiles = $this->resolveRadiusInMiles($radiusInMiles);
        $resolvedMaxCompanies = $this->resolveMaxCompanies($maxCompanies);

        foreach ($keywords as $keyword) {
            $results = $this->getPlaces(
                $keyword,
                $location,
                $geo,
                $searchOrigin,
                $resolvedRadiusInMiles,
                $resolvedMaxCompanies
            );

            foreach ($results as $place) {
                $company = $this->prepareCompanyData($place);

                // Check for duplicates by name, phone, or website
                $duplicate = Company::where(function ($q) use ($company) {
                    $q->where('name', $company['name']);
                    if ($company['phone']) {
                        $q->orWhere('phone', $company['phone']);
                    }
                    if ($company['website']) {
                        $q->orWhere('website', $company['website']);
                    }
                })->first();

                if ($duplicate) {
                    // Save anyway but soft-delete (mark for review)
                    $dup = Company::create($company);
                    $dup->delete();

                    continue;
                }

                $co = Company::create($company);
                // Attach tag
                $tag = Tag::findOrCreate($keyword, 'tag');
                $co->attachTag($tag);

                $totalCount++;
            }
        }

        $this->logUsage(
            $resolvedTier,
            $keywords,
            $resolvedRadiusInMiles,
            $resolvedMaxCompanies,
            $totalCount,
            $location,
            $geo
        );

        return $totalCount;
    }

    /**
     * @param  array{latitude: float, longitude: float}|null  $searchOrigin
     */
    protected function getPlaces(
        string $keyword,
        ?string $location,
        ?string $geo,
        ?array $searchOrigin,
        int $radiusInMiles,
        int $maxCompanies
    ): array {
        $params = $this->buildPlacesParameters(
            $keyword,
            $location,
            $geo,
            $searchOrigin,
            $radiusInMiles
        );

        $results = [];
        do {
            $this->recordGoogleRequest();
            $response = Http::timeout(20)->get($this->placesUrl, $params);
            $payload = $response->json();

            $this->ensureSuccessfulGoogleResponse($response->status(), $payload, 'Google Places text search', ['OK', 'ZERO_RESULTS']);

            $results = array_merge($results, $payload['results'] ?? []);
            $nextPage = $payload['next_page_token'] ?? null;

            if ($nextPage) {
                sleep(2);
                $params['pagetoken'] = $nextPage;
            }
        } while ($nextPage && count($results) < $maxCompanies);

        return array_slice($results, 0, $maxCompanies);
    }

    /**
     * @param  array{latitude: float, longitude: float}|null  $searchOrigin
     * @return array<string, int|string>
     */
    protected function buildPlacesParameters(
        string $keyword,
        ?string $location,
        ?string $geo,
        ?array $searchOrigin,
        int $radiusInMiles
    ): array {
        $params = [
            'query' => $keyword.' near '.($location ?? $geo),
            'key' => $this->googleApiKey,
        ];

        if ($searchOrigin === null) {
            return $params;
        }

        $params['query'] = $keyword;
        $params['location'] = "{$searchOrigin['latitude']},{$searchOrigin['longitude']}";
        $params['radius'] = $this->milesToMeters($radiusInMiles);

        return $params;
    }

    protected function resolveRadiusInMiles(?int $radiusInMiles): int
    {
        if ($radiusInMiles === null || $radiusInMiles < 1) {
            return $this->defaultRadiusInMiles;
        }

        return $radiusInMiles;
    }

    protected function resolveMaxCompanies(?int $maxCompanies): int
    {
        if ($maxCompanies === null || $maxCompanies < 1) {
            return $this->defaultMaxCompanies;
        }

        return $maxCompanies;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected function resolveSearchOrigin(?string $location, ?string $geo): ?array
    {
        if (filled($geo)) {
            return $this->parseCoordinates($geo);
        }

        if (! filled($location)) {
            return null;
        }

        return $this->geocodeLocation($location);
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected function parseCoordinates(string $geo): ?array
    {
        $segments = collect(explode(',', $geo))
            ->map(fn (string $segment): string => trim($segment))
            ->values();

        if ($segments->count() !== 2) {
            return null;
        }

        if (! is_numeric($segments[0]) || ! is_numeric($segments[1])) {
            return null;
        }

        return [
            'latitude' => round((float) $segments[0], 6),
            'longitude' => round((float) $segments[1], 6),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    protected function geocodeLocation(string $location): ?array
    {
        try {
            $this->recordGoogleRequest();

            $response = Http::timeout(20)->get($this->geocodeUrl, [
                'address' => $location,
                'key' => $this->googleApiKey,
            ]);
            $payload = $response->json();

            $this->ensureSuccessfulGoogleResponse($response->status(), $payload, 'Google Geocoding', ['ZERO_RESULTS']);
        } catch (\Exception $e) {
            return null;
        }

        $coordinates = data_get($payload, 'results.0.geometry.location');

        if (! is_array($coordinates)) {
            return null;
        }

        if (! isset($coordinates['lat'], $coordinates['lng'])) {
            return null;
        }

        return [
            'latitude' => round((float) $coordinates['lat'], 6),
            'longitude' => round((float) $coordinates['lng'], 6),
        ];
    }

    protected function milesToMeters(int $radiusInMiles): int
    {
        return min(
            (int) round($radiusInMiles * 1609.344),
            $this->maxRadiusInMeters
        );
    }

    protected function getPlaceDetails(string $placeId): array
    {
        $this->recordGoogleRequest();

        $response = Http::timeout(20)->get($this->detailsUrl, [
            'place_id' => $placeId,
            'fields' => 'name,formatted_phone_number,website',
            'key' => $this->googleApiKey,
        ]);
        $payload = $response->json();

        $this->ensureSuccessfulGoogleResponse($response->status(), $payload, 'Google Place Details', ['ZERO_RESULTS']);

        return $payload['result'] ?? [];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @param  array<int, string>  $allowedStatuses
     */
    protected function ensureSuccessfulGoogleResponse(
        int $httpStatus,
        mixed $payload,
        string $context,
        array $allowedStatuses = ['OK']
    ): void {
        if (! is_array($payload)) {
            throw new RuntimeException("{$context} returned an unreadable response.");
        }

        $status = strtoupper((string) ($payload['status'] ?? ''));

        if ($httpStatus >= 400) {
            $message = $this->extractGoogleErrorMessage($payload);

            throw new RuntimeException("{$context} failed with HTTP {$httpStatus}. {$message}");
        }

        if (in_array($status, $allowedStatuses, true)) {
            return;
        }

        $message = $this->extractGoogleErrorMessage($payload);

        throw new RuntimeException("{$context} returned {$status}. {$message}");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractGoogleErrorMessage(array $payload): string
    {
        $errorMessage = trim((string) ($payload['error_message'] ?? ''));

        if ($errorMessage !== '') {
            return $errorMessage;
        }

        $status = trim((string) ($payload['status'] ?? ''));

        if ($status !== '') {
            return "Google status: {$status}.";
        }

        return 'Google did not provide an error message.';
    }

    protected function enrichWithOpenCorporates(string $name): ?string
    {
        try {
            $res = Http::get($this->openCorporatesUrl, [
                'q' => $name,
                'jurisdiction_code' => 'us_ok',
            ])->json();

            return $res['results']['companies'][0]['company']['registered_address_in_full'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function recordGoogleRequest(): void
    {
        $this->googleRequestCount++;
    }

    protected function logUsage(
        string $tier,
        iterable $keywords,
        int $radiusInMiles,
        int $maxCompanies,
        int $importedCompaniesCount,
        ?string $location,
        ?string $geo
    ): void {
        GooglePlacesUsageLog::query()->create([
            'usage_date' => now()->toDateString(),
            'tier' => $tier,
            'google_request_count' => $this->googleRequestCount,
            'points_used' => $this->googleRequestCount * GooglePlacesUsageLog::pointsForTier($tier),
            'search_terms_count' => collect($keywords)->count(),
            'radius_miles' => $radiusInMiles,
            'max_companies' => $maxCompanies,
            'imported_companies_count' => $importedCompaniesCount,
            'meta' => [
                'location' => $location,
                'geolocation' => $geo,
            ],
        ]);
    }

    protected function prepareCompanyData(array $place): array
    {
        $details = $this->getPlaceDetails($place['place_id']);
        $address = $place['formatted_address'] ?? '';
        $extraAddr = $this->enrich ? $this->enrichWithOpenCorporates($place['name']) : null;

        if ($extraAddr && ! str_contains($address, $extraAddr)) {
            $address .= ' / '.$extraAddr;
        }

        $defaultStatusId = Status::query()->where('slug', 'new')->value('id');

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $place['name'] ?? '',
            'website' => $details['website'] ?? null,
            'email' => null,
            'phone' => $details['formatted_phone_number'] ?? null,
            'address_line1' => $address,
            'city' => $this->extractCity($address),
            'country' => 'US',
            'source' => 'Google Places',
            'status_id' => $defaultStatusId,
            'meta' => json_encode($place),
        ];
    }

    protected function extractCity(string $address): ?string
    {
        if (preg_match('/,\s*([A-Za-z\s]+)\s*,?\s*[A-Z]{2}/', $address, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
