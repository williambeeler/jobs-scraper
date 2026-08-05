<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPreference;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class CompanyTest extends FeatureTestCase
{
    public function test_home_orders_whitelisted_then_verified_then_regular_companies(): void
    {
        Carbon::setTestNow('2026-07-27 12:00:00 UTC');
        $regular = $this->createListing('Regular Role', 'https://example.com/regular', now(), now());
        $verified = $this->createListing('Verified Role', 'https://example.com/verified', now(), now());
        $whitelisted = $this->createListing('Whitelisted Role', 'https://example.com/whitelisted', now(), now());

        foreach ([
            [$regular, 'Regular Company'],
            [$verified, 'Verified Company'],
            [$whitelisted, 'Whitelisted Company'],
        ] as [$listing, $company]) {
            $listing->company()->associate(Company::findOrCreateByName($company))->save();
        }

        CompanyPreference::query()->create([
            'company_id' => $verified->company_id,
            'type' => 'verified',
        ]);
        CompanyPreference::query()->create([
            'company_id' => $whitelisted->company_id,
            'type' => 'whitelist',
        ]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSeeInOrder([$whitelisted->title, $verified->title, $regular->title])
            ->assertSee('list-group-item p-4 job-row-verified', false)
            ->assertSee('Verified')
            ->assertSee('Blacklist')
            ->assertDontSee('Blacklist company')
            ->assertDontSee('First scraped');
    }

    public function test_company_can_be_marked_verified(): void
    {
        $listing = $this->createListing('Verified Company Role', 'https://example.com/verify-company', now(), now());
        $this->post(route('jobs.company-preference', $listing), ['type' => 'verified'])
            ->assertRedirect();

        $this->assertDatabaseHas('company_preferences', [
            'company_id' => $listing->company_id,
            'type' => 'verified',
        ]);
    }

    public function test_company_preference_can_be_updated_asynchronously(): void
    {
        $listing = $this->createListing('Async Company Role', 'https://example.com/async-company', now(), now());

        $this->postJson(route('jobs.company-preference', $listing), ['type' => 'whitelist'])
            ->assertOk()
            ->assertExactJson([
                'company_id' => $listing->company_id,
                'type' => 'whitelist',
            ]);

        $this->assertDatabaseHas('company_preferences', [
            'company_id' => $listing->company_id,
            'type' => 'whitelist',
        ]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('data-company-preference-form', false)
            ->assertSee('data-company-id="'.$listing->company_id.'"', false)
            ->assertSee('data-job-row-star', false);
    }

    public function test_company_normalization_relationships_and_preference_helpers(): void
    {
        $company = Company::findOrCreateByName('  Acme   Corporation ');
        $sameCompany = Company::findOrCreateByName('acme corporation');
        $listing = $this->createListing('Company Relationship Role', 'https://example.com/company-relation', now(), now());
        $listing->company()->associate($company)->save();

        CompanyPreference::query()->create([
            'company_id' => $company->id,
            'type' => 'blacklist',
        ]);

        $this->assertTrue($company->is($sameCompany));
        $this->assertSame('Acme Corporation', $company->name);
        $this->assertSame('acme corporation', $company->name_key);
        $this->assertTrue($company->fresh()->isBlacklisted());
        $this->assertFalse($company->fresh()->isWhitelisted());
        $this->assertFalse($company->fresh()->isVerified());
        $this->assertTrue($company->jobListings()->whereKey($listing)->exists());
        $this->assertFalse(Schema::hasColumn('job_listings', 'company'));
        $this->assertFalse(Schema::hasColumn('job_listings', 'company_key'));
        $this->assertFalse(Schema::hasColumn('company_preferences', 'company_name'));
        $this->assertFalse(Schema::hasColumn('company_preferences', 'company_key'));
    }
}
