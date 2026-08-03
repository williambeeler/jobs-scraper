<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyAveryLabelsBulkActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_companies_can_be_exported_as_avery_pdf(): void
    {
        $status = Status::factory()->create();

        $selectedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => '1-800 Flowers',
            'alias' => 'Flowers HQ',
            'status_id' => $status->id,
            'address_line1' => '123 Main St',
            'address_line2' => 'Suite 200',
            'city' => 'Tampa',
            'state' => 'FL',
            'postal_code' => '33602-1234',
            'country' => 'USA',
        ]);

        $otherCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Should Not Be Included',
            'status_id' => $status->id,
            'address_line1' => '999 Wrong St',
            'city' => 'Nowhere',
            'state' => 'NA',
            'postal_code' => '00000',
            'country' => 'USA',
        ]);

        $response = $this->post(route('crm.companies.bulk-action'), [
            'bulk_action' => 'export_avery_pdf',
            'scope' => 'selected',
            'companies' => [$selectedCompany->id],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
        $response->assertSee('%PDF-1.4', false);
        $response->assertSee('Flowers HQ', false);
        $response->assertDontSee('1-800 Flowers', false);
        $response->assertSee('123 Main St, Suite 200', false);
        $response->assertSee('Tampa, FL 33602', false);
        $response->assertDontSee('USA', false);
        $response->assertDontSee('33602-1234', false);
        $response->assertDontSee($otherCompany->name, false);
    }

    public function test_avery_pdf_export_spans_multiple_pages_when_needed(): void
    {
        $status = Status::factory()->create();
        $selectedIds = [];

        for ($i = 1; $i <= 31; $i++) {
            $company = Company::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => "Label Company {$i}",
                'status_id' => $status->id,
                'address_line1' => "{$i} Test Street",
                'city' => 'Austin',
                'state' => 'TX',
                'postal_code' => '78701',
                'country' => 'USA',
            ]);

            $selectedIds[] = $company->id;
        }

        $response = $this->post(route('crm.companies.bulk-action'), [
            'bulk_action' => 'export_avery_pdf',
            'scope' => 'selected',
            'companies' => $selectedIds,
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertSee('/Count 2', false);
        $response->assertSee('Label Company 31', false);
    }

    public function test_avery_pdf_export_avoids_duplicate_city_state_zip_when_line1_has_full_address(): void
    {
        $status = Status::factory()->create();

        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Overlap Test Co',
            'status_id' => $status->id,
            'address_line1' => '1 Bell Ave, Sandusky, OH 44870, USA',
            'city' => 'Sandusky',
            'state' => 'OH',
            'postal_code' => '44870',
            'country' => 'USA',
        ]);

        $response = $this->post(route('crm.companies.bulk-action'), [
            'bulk_action' => 'export_avery_pdf',
            'scope' => 'selected',
            'companies' => [$company->id],
        ]);

        $response->assertOk();
        $response->assertSee('1 Bell Ave, Sandusky', false);
        $response->assertDontSee('Tm (Sandusky, OH 44870) Tj ET', false);
    }
}
