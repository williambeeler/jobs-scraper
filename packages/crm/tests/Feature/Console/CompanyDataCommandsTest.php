<?php

namespace WilliamBeeler\Crm\Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyDataCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_companies_cleanup_command_normalizes_country_and_parses_full_address_line(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'website' => null,
                'address_line1' => '608 Bell Ave',
                'address_line2' => null,
                'city' => 'Sandusky',
                'state' => 'OH',
                'postal_code' => '44870',
                'country' => 'USA',
                'email' => null,
                'phone' => null,
            ]),
        ]);

        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Cleanup Co',
            'status_id' => $status->id,
            'address_line1' => '608 Bell Ave, Sandusky, OH 44870, United States',
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => 'US',
        ]);

        $this->artisan('companies:cleanup', ['company_id' => $company->id])
            ->assertExitCode(0);

        $company->refresh();
        $this->assertSame('608 Bell Ave', $company->address_line1);
        $this->assertSame('Sandusky', $company->city);
        $this->assertSame('OH', $company->state);
        $this->assertSame('44870', $company->postal_code);
        $this->assertSame('USA', $company->country);
    }

    public function test_companies_enrich_command_fills_missing_fields_with_prism_and_runs_cleanup(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'website' => 'acme.org',
                'address_line1' => '100 Main St, Columbus, OH 43215, US',
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'email' => 'hello@acme.org',
                'phone' => '(555) 123-4567',
                'notes' => 'Official domain found.',
            ]),
            StructuredResponseFake::make()->withStructured([
                'website' => 'https://acme.org',
                'address_line1' => '100 Main St',
                'address_line2' => null,
                'city' => 'Columbus',
                'state' => 'OH',
                'postal_code' => '43215',
                'country' => 'USA',
                'email' => 'hello@acme.org',
                'phone' => '(555) 123-4567',
            ]),
        ]);

        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Acme Church',
            'status_id' => $status->id,
            'website' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'email' => null,
            'phone' => null,
        ]);

        $this->artisan('companies:enrich', ['company_id' => $company->id, '--provider' => 'openai', '--model' => 'gpt-4.1-mini'])
            ->expectsOutputToContain('Token usage: input=')
            ->expectsOutputToContain('Estimated API cost: $')
            ->assertExitCode(0);

        $company->refresh();
        $this->assertSame('https://acme.org', $company->website);
        $this->assertSame('100 Main St', $company->address_line1);
        $this->assertSame('Columbus', $company->city);
        $this->assertSame('OH', $company->state);
        $this->assertSame('43215', $company->postal_code);
        $this->assertSame('USA', $company->country);
        $this->assertSame('hello@acme.org', $company->email);
        $this->assertSame('(555) 123-4567', $company->phone);
        $this->assertIsArray($company->meta);
        $this->assertArrayHasKey('ai_enrichment', $company->meta);
    }

    public function test_companies_enrich_command_uses_manual_website_candidate_when_ai_has_no_website(): void
    {
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'website' => null,
                'address_line1' => null,
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'email' => null,
                'phone' => null,
                'notes' => 'No confident website found.',
            ]),
            StructuredResponseFake::make()->withStructured([
                'website' => 'https://example.org',
                'address_line1' => null,
                'address_line2' => null,
                'city' => null,
                'state' => null,
                'postal_code' => null,
                'country' => null,
                'email' => null,
                'phone' => null,
            ]),
        ]);

        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Manual Candidate Co',
            'status_id' => $status->id,
            'website' => null,
        ]);

        $this->artisan('companies:enrich', [
            'company_id' => $company->id,
            '--provider' => 'openai',
            '--model' => 'gpt-4.1-mini',
            '--website-candidate' => 'https://example.org',
        ])->assertExitCode(0);

        $company->refresh();
        $this->assertSame('https://example.org', $company->website);
    }
}
