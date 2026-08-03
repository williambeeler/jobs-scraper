<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Support\TableNames;

class CompanyDeleteActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_archives_selected_companies(): void
    {
        $status = Status::factory()->create();

        $companyA = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Delete Me A',
            'status_id' => $status->id,
        ]);

        $companyB = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Delete Me B',
            'status_id' => $status->id,
        ]);

        $survivor = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Keep Me',
            'status_id' => $status->id,
        ]);

        $response = $this->post(route('crm.companies.bulk-action'), [
            'bulk_action' => 'bulk_delete',
            'scope' => 'selected',
            'companies' => [$companyA->id, $companyB->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Deleted 2 companies.');

        $this->assertSoftDeleted(TableNames::get('companies'), ['id' => $companyA->id]);
        $this->assertSoftDeleted(TableNames::get('companies'), ['id' => $companyB->id]);
        $this->assertDatabaseHas(TableNames::get('companies'), ['id' => $survivor->id, 'deleted_at' => null]);
    }

    public function test_individual_delete_returns_json_when_requested(): void
    {
        $status = Status::factory()->create();

        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Delete Me',
            'status_id' => $status->id,
        ]);

        $response = $this->deleteJson(route('crm.companies.destroy', $company));

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'message' => 'Company archived.',
        ]);

        $this->assertSoftDeleted(TableNames::get('companies'), ['id' => $company->id]);
    }
}
