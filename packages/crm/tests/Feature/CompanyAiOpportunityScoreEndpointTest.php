<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Services\Companies\CompanyOpportunityScoringService;

class CompanyAiOpportunityScoreEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_opportunity_score_endpoint_returns_success_payload(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Score Target Co',
            'status_id' => $status->id,
            'website' => 'https://score-target.test',
        ]);

        $service = Mockery::mock(CompanyOpportunityScoringService::class);
        $service->shouldReceive('scoreCompany')
            ->once()
            ->andReturn([
                'score' => 67,
                'summary' => 'Strong clarity and conversion path.',
                'website' => 'https://score-target.test',
                'auto_zero' => false,
            ]);
        $this->app->instance(CompanyOpportunityScoringService::class, $service);

        $response = $this->post(route('crm.companies.ai-opportunity-score', $company), []);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'company_id' => $company->id,
            'score' => 67,
            'auto_zero' => false,
        ]);
    }

    public function test_ai_opportunity_score_endpoint_returns_error_when_service_fails(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Broken Score Co',
            'status_id' => $status->id,
        ]);

        $service = Mockery::mock(CompanyOpportunityScoringService::class);
        $service->shouldReceive('scoreCompany')
            ->once()
            ->andThrow(new \RuntimeException('Scoring timeout'));
        $this->app->instance(CompanyOpportunityScoringService::class, $service);

        $response = $this->post(route('crm.companies.ai-opportunity-score', $company), []);

        $response->assertStatus(500);
        $response->assertJson([
            'ok' => false,
            'company_id' => $company->id,
        ]);
        $response->assertJsonPath('message', 'Opportunity scoring failed: Scoring timeout');
    }
}
