<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\Company;
use App\Crm\Models\Status;
use App\Crm\Services\Companies\CompanyOpportunityScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyOpportunityScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_qualifying_website_auto_scores_one_hundred_without_ai_call(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Social Profile Only Co',
            'status_id' => $status->id,
            'website' => 'https://facebook.com/social-profile-only',
        ]);

        $service = app(CompanyOpportunityScoringService::class);
        $result = $service->scoreCompany($company, persist: true);

        $this->assertSame(100, $result['score']);
        $this->assertTrue($result['auto_zero']);
        $company->refresh();
        $this->assertSame(100, $company->opportunity_score_index);
        $this->assertNotNull($company->opportunity_summary);
        $this->assertStringContainsString('custom domain required', $company->opportunity_summary);
        $this->assertIsArray($company->meta);
        $this->assertArrayHasKey('opportunity_scoring', $company->meta);
        $this->assertArrayHasKey('opportunity_scoring_history', $company->meta);
        $this->assertCount(1, $company->meta['opportunity_scoring_history']);
        $this->assertSame(100, $company->meta['opportunity_scoring_history'][0]['score']);
    }

    public function test_missing_website_auto_scores_one_hundred_without_ai_call(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'No Website Co',
            'status_id' => $status->id,
            'website' => null,
        ]);

        $service = app(CompanyOpportunityScoringService::class);
        $firstResult = $service->scoreCompany($company, persist: true);
        $secondResult = $service->scoreCompany($company, persist: true);

        $this->assertSame(100, $firstResult['score']);
        $this->assertTrue($firstResult['auto_zero']);
        $this->assertSame(100, $secondResult['score']);
        $this->assertTrue($secondResult['auto_zero']);
        $company->refresh();
        $this->assertSame(100, $company->opportunity_score_index);
        $this->assertCount(2, $company->meta['opportunity_scoring_history']);
    }
}
