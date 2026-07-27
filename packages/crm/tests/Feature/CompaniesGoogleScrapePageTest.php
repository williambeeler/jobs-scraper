<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\GooglePlacesUsageLog;

class CompaniesGoogleScrapePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_scrape_page_loads_in_companies_workspace(): void
    {
        $response = $this->get(route('companies.googleScrape'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Companies/GoogleScrape')
            ->where('selectedRangeDays', 30)
            ->has('availableRangeDays', 4)
            ->has('tiers', 3)
            ->where('tiers.0.label', 'Essentials')
            ->where('monthlyUsage.point_limit', 10000)
        );
    }

    public function test_companies_midrail_shows_google_scrape_entry_on_page(): void
    {
        $response = $this->get(route('companies.googleScrape'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Companies/GoogleScrape')
            ->where('selectedRangeDays', 30)
        );
    }

    public function test_google_scrape_page_shows_logged_usage_metrics(): void
    {
        GooglePlacesUsageLog::query()->create([
            'usage_date' => now()->toDateString(),
            'tier' => GooglePlacesUsageLog::TierPro,
            'google_request_count' => 14,
            'points_used' => 28,
            'search_terms_count' => 2,
            'radius_miles' => 10,
            'max_companies' => 20,
            'imported_companies_count' => 9,
        ]);

        $response = $this->get(route('companies.googleScrape', ['usage_range' => 30]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Companies/GoogleScrape')
            ->where('selectedRangeDays', 30)
            ->where('monthlyUsage.points_used', 28)
            ->where('monthlyUsage.point_limit', 10000)
            ->where('monthlyUsage.request_count', 14)
            ->where('tiers.1.points', 2)
        );
    }
}
