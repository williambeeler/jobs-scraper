<?php

namespace Tests\Unit\Crm;

use App\Crm\Models\GooglePlacesUsageLog;
use App\Crm\Services\LeadScraperService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeadScraperServiceTest extends TestCase
{
    public function test_it_builds_places_parameters_with_radius_for_geolocation_searches(): void
    {
        $service = new class extends LeadScraperService
        {
            public function buildParametersForTest(
                string $keyword,
                ?string $location,
                ?string $geo,
                ?array $searchOrigin,
                int $radiusInMiles
            ): array {
                return $this->buildPlacesParameters($keyword, $location, $geo, $searchOrigin, $radiusInMiles);
            }
        };

        $parameters = $service->buildParametersForTest(
            'restaurant',
            null,
            '35.6528,-97.4781',
            [
                'latitude' => 35.6528,
                'longitude' => -97.4781,
            ],
            10
        );

        $this->assertSame('restaurant', $parameters['query']);
        $this->assertSame('35.6528,-97.4781', $parameters['location']);
        $this->assertSame(16093, $parameters['radius']);
    }

    public function test_it_falls_back_to_query_bias_only_when_search_origin_is_missing(): void
    {
        $service = new class extends LeadScraperService
        {
            public function buildParametersForTest(
                string $keyword,
                ?string $location,
                ?string $geo,
                ?array $searchOrigin,
                int $radiusInMiles
            ): array {
                return $this->buildPlacesParameters($keyword, $location, $geo, $searchOrigin, $radiusInMiles);
            }
        };

        $parameters = $service->buildParametersForTest(
            'restaurant',
            'Midwest City, OK',
            null,
            null,
            10
        );

        $this->assertSame('restaurant near Midwest City, OK', $parameters['query']);
        $this->assertArrayNotHasKey('location', $parameters);
        $this->assertArrayNotHasKey('radius', $parameters);
    }

    public function test_it_resolves_max_companies_with_a_positive_default(): void
    {
        $service = new class extends LeadScraperService
        {
            public function resolveMaxCompaniesForTest(?int $maxCompanies): int
            {
                return $this->resolveMaxCompanies($maxCompanies);
            }
        };

        $this->assertSame(20, $service->resolveMaxCompaniesForTest(null));
        $this->assertSame(20, $service->resolveMaxCompaniesForTest(0));
        $this->assertSame(7, $service->resolveMaxCompaniesForTest(7));
    }

    public function test_it_can_normalize_supported_tiers_for_usage_logging(): void
    {
        $this->assertSame('essentials', GooglePlacesUsageLog::normalizeTier(null));
        $this->assertSame('pro', GooglePlacesUsageLog::normalizeTier('pro'));
        $this->assertSame('enterprise', GooglePlacesUsageLog::normalizeTier('enterprise'));
        $this->assertSame('essentials', GooglePlacesUsageLog::normalizeTier('invalid'));
    }

    public function test_it_throws_a_helpful_exception_when_google_places_rejects_the_request(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'REQUEST_DENIED',
                'error_message' => 'This API project is not authorized to use this API.',
            ]),
        ]);

        $service = new class extends LeadScraperService
        {
            public function getPlacesForTest(
                string $keyword,
                ?string $location,
                ?string $geo,
                ?array $searchOrigin,
                int $radiusInMiles,
                int $maxCompanies
            ): array {
                return $this->getPlaces($keyword, $location, $geo, $searchOrigin, $radiusInMiles, $maxCompanies);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Places text search returned REQUEST_DENIED. This API project is not authorized to use this API.');

        $service->getPlacesForTest(
            'restaurant',
            null,
            '37.7414,-97.3053',
            [
                'latitude' => 37.7414,
                'longitude' => -97.3053,
            ],
            25,
            50
        );
    }

    public function test_it_allows_zero_results_without_throwing(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'ZERO_RESULTS',
                'results' => [],
            ]),
        ]);

        $service = new class extends LeadScraperService
        {
            public function getPlacesForTest(
                string $keyword,
                ?string $location,
                ?string $geo,
                ?array $searchOrigin,
                int $radiusInMiles,
                int $maxCompanies
            ): array {
                return $this->getPlaces($keyword, $location, $geo, $searchOrigin, $radiusInMiles, $maxCompanies);
            }
        };

        $results = $service->getPlacesForTest(
            'restaurant',
            null,
            '37.7414,-97.3053',
            [
                'latitude' => 37.7414,
                'longitude' => -97.3053,
            ],
            25,
            50
        );

        $this->assertSame([], $results);
    }
}
