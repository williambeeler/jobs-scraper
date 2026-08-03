<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;

class ToolsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_renders_template_download_and_upload_copy(): void
    {
        Status::factory()->create([
            'name' => 'New',
            'slug' => 'new',
            'is_default' => true,
        ]);

        Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Default relationship',
        ]);

        $response = $this->get(route('crm.tools.import'));

        $response->assertOk();
        $response->assertViewIs('crm::tools.import');
        $response->assertViewHas('defaultStatusId', 1);
        $response->assertViewHas('leadRelationshipId', 1);
        $response->assertViewHas('statuses', fn ($statuses) => $statuses->count() === 1);
        $response->assertViewHas('relationships', fn ($relationships) => $relationships->count() === 1);
        $response->assertViewHas('templateDownloadUrl', fn (string $url) => str_contains($url, 'company-import-template.csv'));
    }

    public function test_company_import_template_downloads_from_the_package(): void
    {
        $this->get(route('crm.tools.import.template'))
            ->assertOk()
            ->assertDownload('company-import-template.csv');
    }

    public function test_company_import_honors_valid_csv_ids_and_falls_back_to_selected_classification_values(): void
    {
        Status::factory()->create([
            'name' => 'New',
            'slug' => 'new',
            'is_default' => true,
        ]);

        $validStatus = Status::factory()->create([
            'name' => 'Qualified',
            'slug' => 'qualified',
        ]);

        $leadRelationship = Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Default relationship',
        ]);

        $validRelationship = Relationship::query()->create([
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => 'Existing customer',
        ]);

        $csv = implode("\n", [
            'name,website,status_id,relationship_id,id,uuid,opportunity_score,opportunity_summary,meta,source',
            "Alpha Co,https://alpha.test,{$validStatus->id},{$validRelationship->id},999,abc,88,Ignore this,\"{\"\"foo\"\":\"\"bar\"\"}\",CSV Source",
            'Bravo Co,https://bravo.test,100,200,998,def,66,Ignore this too,"{""bar"":""baz""}",CSV Source',
        ]);

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csv);

        $response = $this->post(route('crm.tools.import.companies'), [
            'file' => $file,
            'campaigns' => 'Spring Push',
            'tags' => 'Priority,New Import',
            'source' => 'Import Tool',
            'status_id' => $validStatus->id,
            'relationship_id' => $validRelationship->id,
        ]);

        $response->assertOk();

        $streamedContent = $response->streamedContent();

        $this->assertStringContainsString('"type":"complete"', $streamedContent);
        $this->assertSame(2, Company::query()->count());

        $alpha = Company::query()->where('name', 'Alpha Co')->firstOrFail();
        $bravo = Company::query()->where('name', 'Bravo Co')->firstOrFail();

        $this->assertSame($validStatus->id, $alpha->status_id);
        $this->assertSame($validRelationship->id, $alpha->relationship_id);
        $this->assertSame($validStatus->id, $bravo->status_id);
        $this->assertSame($validRelationship->id, $bravo->relationship_id);
        $this->assertSame('Import Tool', $alpha->source);
        $this->assertSame('Import Tool', $bravo->source);
        $this->assertNull($alpha->meta);
        $this->assertNull($bravo->meta);
        $this->assertTrue($alpha->tagsWithType('campaign')->pluck('name')->contains('Spring Push'));
        $this->assertTrue($alpha->tagsWithType('tag')->pluck('name')->contains('Priority'));
        $this->assertTrue($bravo->tagsWithType('tag')->pluck('name')->contains('New Import'));
    }

    public function test_company_import_falls_back_to_selected_dropdown_ids_when_csv_values_are_blank_or_invalid(): void
    {
        $fallbackStatus = Status::factory()->create([
            'name' => 'New',
            'slug' => 'new',
            'is_default' => true,
        ]);

        $csvStatus = Status::factory()->create([
            'name' => 'Qualified',
            'slug' => 'qualified',
        ]);

        $fallbackRelationship = Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Default relationship',
        ]);

        $csvRelationship = Relationship::query()->create([
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => 'Existing customer',
        ]);

        $csv = implode("\n", [
            'name,website,status_id,relationship_id,source',
            "Alpha Co,https://alpha.test,{$csvStatus->id},{$csvRelationship->id},CSV Source",
            'Bravo Co,https://bravo.test,100,200,CSV Source',
            'Charlie Co,https://charlie.test,,,CSV Source',
        ]);

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csv);

        $response = $this->post(route('crm.tools.import.companies'), [
            'file' => $file,
            'campaigns' => 'Spring Push',
            'tags' => 'Priority',
            'source' => 'Import Tool',
            'status_id' => $fallbackStatus->id,
            'relationship_id' => $fallbackRelationship->id,
        ]);

        $response->assertOk();
        $response->streamedContent();

        $alpha = Company::query()->where('name', 'Alpha Co')->firstOrFail();
        $bravo = Company::query()->where('name', 'Bravo Co')->firstOrFail();
        $charlie = Company::query()->where('name', 'Charlie Co')->firstOrFail();

        $this->assertSame($csvStatus->id, $alpha->status_id);
        $this->assertSame($csvRelationship->id, $alpha->relationship_id);
        $this->assertSame($fallbackStatus->id, $bravo->status_id);
        $this->assertSame($fallbackRelationship->id, $bravo->relationship_id);
        $this->assertSame($fallbackStatus->id, $charlie->status_id);
        $this->assertSame($fallbackRelationship->id, $charlie->relationship_id);
    }

    public function test_company_import_only_skips_rows_with_blank_names(): void
    {
        $status = Status::factory()->create([
            'name' => 'New',
            'slug' => 'new',
            'is_default' => true,
        ]);

        $relationship = Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Default relationship',
        ]);

        $csv = implode("\n", [
            'name,website,email,poc_email,source',
            'No Website Co,,,,',
            'Raw Website Co,not-a-valid-url,owner at example dot com,contact at raw dot test,CSV Source',
            '   ,https://should-skip.test,skip@example.com,skip@example.com,CSV Source',
        ]);

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csv);

        $response = $this->post(route('crm.tools.import.companies'), [
            'file' => $file,
            'campaigns' => '',
            'tags' => '',
            'source' => '',
            'status_id' => $status->id,
            'relationship_id' => $relationship->id,
        ]);

        $response->assertOk();

        $streamedContent = $response->streamedContent();

        $this->assertStringContainsString('"imported":2', $streamedContent);
        $this->assertStringContainsString('"skipped":1', $streamedContent);
        $this->assertSame(2, Company::query()->count());

        $noWebsite = Company::query()->where('name', 'No Website Co')->firstOrFail();
        $rawWebsite = Company::query()->where('name', 'Raw Website Co')->firstOrFail();

        $this->assertNull($noWebsite->website);
        $this->assertNull($noWebsite->email);
        $this->assertSame('not-a-valid-url', $rawWebsite->website);
        $this->assertSame('owner at example dot com', $rawWebsite->email);
        $this->assertSame('contact at raw dot test', $rawWebsite->poc_email);
    }
}
