<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyCampaignBulkActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_campaign_assigns_campaigns_to_selected_companies(): void
    {
        $status = Status::factory()->create();

        $companyA = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Alpha Co',
            'status_id' => $status->id,
        ]);

        $companyB = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bravo Co',
            'status_id' => $status->id,
        ]);

        $response = $this->post(route('companies.bulk-action'), [
            'bulk_action' => 'bulk_campaign',
            'scope' => 'selected',
            'companies' => [$companyA->id, $companyB->id],
            'campaigns' => ['Spring Brochure', 'Q2 Direct Mail'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Bulk campaign assignment complete.');

        $this->assertTrue($companyA->fresh()->tagsWithType('campaign')->pluck('name')->contains('Spring Brochure'));
        $this->assertTrue($companyA->fresh()->tagsWithType('campaign')->pluck('name')->contains('Q2 Direct Mail'));
        $this->assertTrue($companyB->fresh()->tagsWithType('campaign')->pluck('name')->contains('Spring Brochure'));
        $this->assertTrue($companyB->fresh()->tagsWithType('campaign')->pluck('name')->contains('Q2 Direct Mail'));
    }
}
