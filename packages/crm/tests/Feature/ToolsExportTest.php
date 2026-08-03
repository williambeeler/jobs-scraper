<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use WilliamBeeler\Crm\Exports\CompaniesTableExport;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Services\CompanyExportService;
use WilliamBeeler\Crm\Support\TableNames;

class ToolsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_page_shows_current_business_count(): void
    {
        $status = Status::factory()->create();

        Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Alpha Export Co',
            'status_id' => $status->id,
        ]);

        Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Bravo Export Co',
            'status_id' => $status->id,
        ]);

        $response = $this->get(route('crm.tools.export'));

        $response->assertOk();
        $response->assertViewIs('crm::tools.export');
        $response->assertViewHas('companyCount', 2);
        $response->assertViewHas('exportOptions', fn (array $options) => count($options) === 2
            && $options[0]['label'] === 'Export All Businesses as CSV'
            && $options[1]['label'] === 'Export All Businesses as XLSX');
    }

    public function test_company_export_builds_csv_with_database_columns_and_all_rows(): void
    {
        Storage::fake('local');

        $status = Status::factory()->create();

        $activeCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Active Export Co',
            'alias' => 'AEC',
            'email' => 'active@example.com',
            'status_id' => $status->id,
            'meta' => ['segment' => 'active'],
        ]);

        $trashedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Trashed Export Co',
            'status_id' => $status->id,
            'meta' => ['segment' => 'trashed'],
        ]);
        $trashedCompany->delete();

        /** @var CompanyExportService $service */
        $service = app(CompanyExportService::class);
        $export = $service->createExport(CompanyExportService::TYPE_COMPANIES_CSV_ALL);

        $service->buildExport($export['id']);

        $statusPayload = $service->getExportStatus($export['id']);

        $this->assertNotNull($statusPayload);
        $this->assertSame(CompanyExportService::STATUS_COMPLETED, $statusPayload['status']);
        $this->assertSame(2, $statusPayload['processed']);
        $this->assertNotEmpty($statusPayload['path']);

        $csv = Storage::disk('local')->get($statusPayload['path']);
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $header = str_getcsv($lines[0]);
        $firstRow = array_combine($header, str_getcsv($lines[1]));
        $secondRow = array_combine($header, str_getcsv($lines[2]));

        $this->assertSame(Schema::getColumnListing(TableNames::get('companies')), $header);
        $this->assertStringContainsString($activeCompany->name, $csv);
        $this->assertStringContainsString($trashedCompany->name, $csv);
        $this->assertSame('{"segment":"active"}', $firstRow['meta']);
        $this->assertSame('{"segment":"trashed"}', $secondRow['meta']);
    }

    public function test_export_generator_updates_progress_before_completion(): void
    {
        $status = Status::factory()->create();

        foreach (range(1, 30) as $index) {
            Company::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => 'Progress Co '.$index,
                'status_id' => $status->id,
            ]);
        }

        $exportId = (string) Str::uuid();

        Cache::put('company-export:'.$exportId, [
            'type' => CompanyExportService::TYPE_COMPANIES_CSV_ALL,
            'status' => CompanyExportService::STATUS_RUNNING,
            'total' => 30,
            'processed' => 0,
            'filename' => null,
            'path' => null,
            'token' => Str::random(40),
            'error' => null,
        ], now()->addDay());

        $export = new CompaniesTableExport(Schema::getColumnListing(TableNames::get('companies')), $exportId, 30);

        $rows = iterator_to_array($export->generator());

        $this->assertCount(30, $rows);
        $this->assertSame(30, Cache::get('company-export:'.$exportId)['processed']);
    }

    public function test_company_export_builds_xlsx_with_database_columns_and_all_rows(): void
    {
        Storage::fake('local');

        $status = Status::factory()->create();

        $activeCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Excel Export Co',
            'alias' => 'EEC',
            'email' => 'excel@example.com',
            'status_id' => $status->id,
            'meta' => ['segment' => 'excel'],
        ]);

        $trashedCompany = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Deleted Excel Co',
            'status_id' => $status->id,
            'meta' => ['segment' => 'deleted'],
        ]);
        $trashedCompany->delete();

        /** @var CompanyExportService $service */
        $service = app(CompanyExportService::class);
        $export = $service->createExport(CompanyExportService::TYPE_COMPANIES_XLSX_ALL);

        $service->buildExport($export['id']);

        $statusPayload = $service->getExportStatus($export['id']);

        $this->assertNotNull($statusPayload);
        $this->assertSame(CompanyExportService::STATUS_COMPLETED, $statusPayload['status']);
        $this->assertSame(2, $statusPayload['processed']);
        $this->assertNotEmpty($statusPayload['path']);
        $this->assertStringEndsWith('.xlsx', (string) $statusPayload['filename']);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($statusPayload['path']));
        $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $this->assertSame(Schema::getColumnListing(TableNames::get('companies')), $sheetRows[0]);
        $this->assertContains($activeCompany->name, $sheetRows[1]);
        $this->assertContains($trashedCompany->name, $sheetRows[2]);
        $this->assertContains('{"segment":"excel"}', $sheetRows[1]);
        $this->assertContains('{"segment":"deleted"}', $sheetRows[2]);
    }
}
