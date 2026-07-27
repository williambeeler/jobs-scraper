<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Services\Companies\CompanyAiEnrichmentService;

class CompanyAiEnrichCleanEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_enrich_clean_endpoint_returns_success_payload(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'AI Target Co',
            'status_id' => $status->id,
        ]);

        $service = Mockery::mock(CompanyAiEnrichmentService::class);
        $service->shouldReceive('enrichCompany')
            ->once()
            ->andReturn([
                'applied' => [
                    'website' => ['from' => null, 'to' => 'https://example.com'],
                ],
                'cleanup' => [
                    'country' => ['from' => 'US', 'to' => 'USA'],
                ],
            ]);
        $this->app->instance(CompanyAiEnrichmentService::class, $service);

        $response = $this->post(route('companies.ai-enrich-clean', $company), []);

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'company_id' => $company->id,
            'applied_count' => 1,
            'cleanup_count' => 1,
        ]);
    }

    public function test_ai_enrich_clean_endpoint_returns_error_when_service_fails(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Broken AI Co',
            'status_id' => $status->id,
        ]);

        $service = Mockery::mock(CompanyAiEnrichmentService::class);
        $service->shouldReceive('enrichCompany')
            ->once()
            ->andThrow(new \RuntimeException('Upstream timeout'));
        $this->app->instance(CompanyAiEnrichmentService::class, $service);

        $response = $this->post(route('companies.ai-enrich-clean', $company), []);

        $response->assertStatus(500);
        $response->assertJson([
            'ok' => false,
            'company_id' => $company->id,
        ]);
        $response->assertJsonPath('message', 'AI enrich/clean failed: Upstream timeout');
    }
}
