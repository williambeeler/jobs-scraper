<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Support\TableNames;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;

class CompanyModalInteractionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_modal_endpoint_renders_company_details(): void
    {
        $status = Status::factory()->create(['name' => 'Prospect']);
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Acme Logistics',
            'status_id' => $status->id,
        ]);

        $response = $this->get(route('companies.modal', $company));

        $response->assertOk();
        $response->assertSee('Acme Logistics');
        $response->assertSee('Company details, notes, and phone call history');
        $response->assertSee('Phone Calls');
    }

    public function test_company_modal_note_submission_returns_refreshed_html(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Northwind Roofing',
            'status_id' => $status->id,
        ]);

        $response = $this->postJson(route('companies.notes.store', $company), [
            'content' => '<p>Left a voicemail.</p>',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'Note saved.',
        ]);

        $this->assertDatabaseHas(TableNames::get('notes'), [
            'company_id' => $company->id,
            'content' => '<p>Left a voicemail.</p>',
        ]);

        $this->assertStringContainsString('Left a voicemail.', $response->json('noteHtml'));
    }

    public function test_company_modal_phone_call_submission_returns_refreshed_html(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Blue Harbor Supply',
            'status_id' => $status->id,
        ]);

        $response = $this->postJson(route('companies.phone-calls.store', $company), [
            'called_at' => '2026-03-12 10:30:00',
            'notes' => '<p>Discussed pricing and next steps.</p>',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'Phone call saved.',
        ]);

        $this->assertDatabaseHas(TableNames::get('phone_calls'), [
            'company_id' => $company->id,
            'called_at' => '2026-03-12 10:30:00',
            'notes' => '<p>Discussed pricing and next steps.</p>',
        ]);

        $this->assertStringContainsString('Discussed pricing and next steps.', $response->json('phoneCallHtml'));
    }

    public function test_company_callback_preferences_can_be_updated_from_modal(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Callback Co',
            'status_id' => $status->id,
        ]);

        $response = $this->patchJson(route('companies.callback.update', $company), [
            'needs_callback' => 1,
            'callback_at' => '2026-03-14 09:15:00',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'Callback preference saved.',
            'needsCallback' => true,
            'callbackAt' => '2026-03-14T09:15',
        ]);

        $this->assertDatabaseHas(TableNames::get('companies'), [
            'id' => $company->id,
            'needs_callback' => 1,
            'callback_at' => '2026-03-14 09:15:00',
        ]);
    }

    public function test_company_classification_can_be_updated_from_modal(): void
    {
        $status = Status::factory()->create(['slug' => 'new']);
        $replacementStatus = Status::factory()->create(['name' => 'Qualified', 'slug' => 'qualified']);
        $relationship = Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Someone who has not been contacted yet or minimally qualified.',
        ]);
        $replacementRelationship = Relationship::query()->create([
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => 'A paying client with an active or completed project.',
        ]);

        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Classified Co',
            'status_id' => $status->id,
            'relationship_id' => $relationship->id,
        ]);

        $response = $this->patchJson(route('companies.classification.update', $company), [
            'status_id' => $replacementStatus->id,
            'relationship_id' => $replacementRelationship->id,
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'Company classification saved.',
            'statusId' => $replacementStatus->id,
            'relationshipId' => $replacementRelationship->id,
        ]);

        $this->assertDatabaseHas(TableNames::get('companies'), [
            'id' => $company->id,
            'status_id' => $replacementStatus->id,
            'relationship_id' => $replacementRelationship->id,
        ]);
    }
}
