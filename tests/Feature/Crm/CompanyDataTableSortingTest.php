<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\Company;
use App\Crm\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyDataTableSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_companies_data_endpoint_can_sort_by_campaigns_without_error(): void
    {
        $status = Status::factory()->create();

        $companyA = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Alpha Company',
            'status_id' => $status->id,
        ]);
        $companyA->attachTag('Spring Campaign', 'campaign');

        $companyB = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bravo Company',
            'status_id' => $status->id,
        ]);
        $companyB->attachTag('Fall Campaign', 'campaign');

        $response = $this->get(route('crm.companies.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => [
                ['data' => 'select', 'name' => 'select'],
                ['data' => 'name', 'name' => 'name'],
                ['data' => 'tags', 'name' => 'tags'],
                ['data' => 'campaigns', 'name' => 'campaigns'],
                ['data' => 'status', 'name' => 'status'],
                ['data' => 'opportunity_score_index', 'name' => 'opportunity_score_index'],
                ['data' => 'website', 'name' => 'website'],
                ['data' => 'actions', 'name' => 'actions'],
            ],
            'order' => [
                ['column' => 3, 'dir' => 'asc'],
            ],
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
        $this->assertCount(2, $response->json('data'));
    }
}
