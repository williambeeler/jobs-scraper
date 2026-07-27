<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyDataExportsBulkActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_companies_csv_export_includes_extended_company_fields(): void
    {
        $status = Status::factory()->create(['name' => 'Prospect']);

        $selectedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Expanded Export Co',
            'website' => 'https://example.com',
            'email' => 'hello@example.com',
            'phone' => '813-555-0100',
            'poc_name' => 'Alex Rivera',
            'poc_email' => 'alex@example.com',
            'poc_phone' => '813-555-0101',
            'address_line1' => '123 Main St',
            'address_line2' => 'Suite 200',
            'city' => 'Tampa',
            'state' => 'FL',
            'postal_code' => '33602',
            'country' => 'USA',
            'source' => 'Referral',
            'status_id' => $status->id,
            'opportunity_score_index' => 88,
            'opportunity_summary' => 'Strong fit and ready for outreach',
            'meta' => ['owner' => 'sales', 'priority' => 'high'],
        ]);
        $selectedCompany->attachTag('Important', 'tag');
        $selectedCompany->attachTag('Spring Brochure', 'campaign');

        $otherCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Do Not Export Me',
            'status_id' => $status->id,
        ]);

        $response = $this->post(route('companies.bulk-action'), [
            'bulk_action' => 'export_csv',
            'scope' => 'selected',
            'companies' => [$selectedCompany->id],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $header = str_getcsv($lines[0]);
        $row = str_getcsv($lines[1]);
        $rowByHeader = array_combine($header, $row);

        $this->assertContains('Address Line 1', $header);
        $this->assertContains('Opportunity Summary', $header);
        $this->assertContains('Meta', $header);
        $this->assertContains('Tags', $header);
        $this->assertContains('Campaigns', $header);

        $this->assertStringContainsString('Expanded Export Co', $csv);
        $this->assertStringContainsString('123 Main St', $csv);
        $this->assertStringContainsString('Strong fit and ready for outreach', $csv);
        $this->assertStringContainsString('Important', $csv);
        $this->assertStringContainsString('Spring Brochure', $csv);
        $this->assertStringNotContainsString($otherCompany->name, $csv);
        $this->assertSame('{"owner":"sales","priority":"high"}', $rowByHeader['Meta']);
        $this->assertSame('Spring Brochure', $rowByHeader['Campaigns']);
    }

    public function test_selected_companies_excel_export_includes_extended_company_fields(): void
    {
        $status = Status::factory()->create(['name' => 'Qualified']);

        $selectedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Spreadsheet Export Co',
            'address_line1' => '456 Market Ave',
            'source' => 'Inbound',
            'status_id' => $status->id,
            'opportunity_score_index' => 72,
            'opportunity_summary' => 'Warm lead',
            'meta' => ['segment' => 'mid-market'],
        ]);
        $selectedCompany->attachTag('Priority', 'tag');

        $response = $this->post(route('companies.bulk-action'), [
            'bulk_action' => 'export_excel',
            'scope' => 'selected',
            'companies' => [$selectedCompany->id],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel');

        $excel = $response->streamedContent();
        $this->assertStringContainsString('<th>Address Line 1</th>', $excel);
        $this->assertStringContainsString('<th>Opportunity Summary</th>', $excel);
        $this->assertStringContainsString('<th>Meta</th>', $excel);
        $this->assertStringContainsString('Spreadsheet Export Co', $excel);
        $this->assertStringContainsString('456 Market Ave', $excel);
        $this->assertStringContainsString('Warm lead', $excel);
        $this->assertStringContainsString('{&quot;segment&quot;:&quot;mid-market&quot;}', $excel);
        $this->assertStringContainsString('Priority', $excel);
    }

    public function test_selected_companies_pdf_export_downloads_selected_list(): void
    {
        $status = Status::factory()->create();

        $selectedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'PDF Export Co',
            'status_id' => $status->id,
            'address_line1' => '77 Export Ave',
            'city' => 'Orlando',
            'state' => 'FL',
            'postal_code' => '32801',
            'country' => 'USA',
            'website' => 'https://pdf.example.com',
            'phone' => '407-555-0100',
            'email' => 'pdf@example.com',
        ]);

        $otherCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Ignore Me Co',
            'status_id' => $status->id,
        ]);

        $response = $this->post(route('companies.bulk-action'), [
            'bulk_action' => 'export_pdf',
            'scope' => 'selected',
            'companies' => [$selectedCompany->id],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
        $response->assertSee('%PDF-1.4', false);
        $response->assertSee('1 Selected Companies on '.now()->toDateString(), false);
        $response->assertSee('PDF Export Co', false);
        $response->assertSee('77 Export Ave', false);
        $response->assertSee('Website: https://pdf.example.com', false);
        $response->assertSee('Phone: 407-555-0100', false);
        $response->assertSee('Email: pdf@example.com', false);
        $response->assertDontSee($otherCompany->name, false);
    }

    public function test_selected_companies_vcard_export_includes_multiple_contacts(): void
    {
        $status = Status::factory()->create();

        $companyOne = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Alpha Systems',
            'status_id' => $status->id,
            'poc_name' => 'Ada Lovelace',
            'poc_email' => 'ada@alpha.test',
            'poc_phone' => '813-555-0100',
            'address_line1' => '101 Main St',
            'city' => 'Tampa',
            'state' => 'FL',
            'postal_code' => '33602',
            'country' => 'USA',
            'website' => 'https://alpha.test',
        ]);

        $companyTwo = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bravo Labs',
            'status_id' => $status->id,
            'poc_name' => 'Grace Hopper',
            'poc_email' => 'grace@bravo.test',
            'poc_phone' => '407-555-0199',
        ]);

        $notSelected = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Ignore Co',
            'status_id' => $status->id,
            'poc_name' => 'Ignore Person',
        ]);

        $response = $this->post(route('companies.bulk-action'), [
            'bulk_action' => 'export_vcard',
            'scope' => 'selected',
            'companies' => [$companyOne->id, $companyTwo->id],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/vcard; charset=UTF-8');
        $response->assertHeader('Content-Disposition');

        $vcf = $response->getContent();
        $this->assertSame(2, substr_count($vcf, 'BEGIN:VCARD'));
        $this->assertSame(2, substr_count($vcf, 'END:VCARD'));
        $this->assertStringContainsString('FN:Ada Lovelace', $vcf);
        $this->assertStringContainsString('ORG:Alpha Systems', $vcf);
        $this->assertStringContainsString('EMAIL;TYPE=INTERNET:ada@alpha.test', $vcf);
        $this->assertStringContainsString('FN:Grace Hopper', $vcf);
        $this->assertStringContainsString('ORG:Bravo Labs', $vcf);
        $this->assertStringNotContainsString($notSelected->name, $vcf);
    }
}
