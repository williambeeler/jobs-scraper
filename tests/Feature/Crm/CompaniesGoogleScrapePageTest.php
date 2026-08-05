<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\GooglePlacesUsageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompaniesGoogleScrapePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_scrape_page_loads_in_companies_workspace(): void
    {
        $response = $this->get(route('crm.companies.googleScrape'));

        $response->assertOk();
        $response->assertViewIs('crm.companies.google-scrape');
        $response->assertViewHas('selectedRangeDays', 30);
        $response->assertViewHas('availableRangeDays', fn (array $days) => count($days) === 4);
        $response->assertViewHas('tiers', fn (array $tiers) => count($tiers) === 3 && $tiers[0]['label'] === 'Essentials');
        $response->assertViewHas('monthlyUsage', fn (array $usage) => $usage['point_limit'] === 10000);
    }

    public function test_companies_midrail_shows_google_scrape_entry_on_page(): void
    {
        $response = $this->get(route('crm.companies.googleScrape'));

        $response->assertOk();
        $response->assertViewIs('crm.companies.google-scrape');
        $response->assertViewHas('selectedRangeDays', 30);
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

        $response = $this->get(route('crm.companies.googleScrape', ['usage_range' => 30]));

        $response->assertOk();
        $response->assertViewIs('crm.companies.google-scrape');
        $response->assertViewHas('selectedRangeDays', 30);
        $response->assertViewHas('monthlyUsage', fn (array $usage) => $usage['points_used'] === 28 && $usage['point_limit'] === 10000 && $usage['request_count'] === 14);
        $response->assertViewHas('tiers', fn (array $tiers) => $tiers[1]['points'] === 2);
    }
}
