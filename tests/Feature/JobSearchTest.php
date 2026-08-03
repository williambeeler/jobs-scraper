<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPreference;

class JobSearchTest extends FeatureTestCase
{
    public function test_job_search_includes_hidden_jobs_and_filters_by_company_type_and_salary(): void
    {
        $blacklisted = $this->createListing('Legacy PHP Engineer', 'https://example.com/blacklisted-search', now(), now());
        $blacklistedCompany = Company::findOrCreateByName('Blacklisted Search Company');
        $blacklisted->company()->associate($blacklistedCompany)->save();
        $blacklisted->update(['status' => 'not_interested']);
        CompanyPreference::query()->create(['company_id' => $blacklistedCompany->id, 'type' => 'blacklist']);

        $verified = $this->createListing('Remote Laravel Engineer', 'https://example.com/verified-search', now(), now());
        $verifiedCompany = Company::findOrCreateByName('Verified Search Company');
        $verified->company()->associate($verifiedCompany)->save();
        $verified->update(['salary' => '$150,000–$175,000', 'location' => 'Remote, USA']);
        CompanyPreference::query()->create(['company_id' => $verifiedCompany->id, 'type' => 'verified']);

        $regular = $this->createListing('WordPress Developer', 'https://example.com/regular-search', now(), now());
        $regular->company()->associate(Company::findOrCreateByName('Regular Search Company'))->save();

        $this->get(route('jobs.search'))
            ->assertOk()
            ->assertSee('Job Search')
            ->assertSee('Ready to search through 3 jobs')
            ->assertDontSee($blacklisted->title)
            ->assertDontSee($verified->title)
            ->assertDontSee($regular->title);

        $this->get(route('jobs.search', ['company_type' => 'any']))
            ->assertOk()
            ->assertSee($blacklisted->title)
            ->assertSee($verified->title)
            ->assertSee($regular->title)
            ->assertSee('Blacklisted');

        $this->get(route('jobs.search', ['company_type' => 'blacklist']))
            ->assertOk()
            ->assertSee($blacklisted->title)
            ->assertDontSee($verified->title)
            ->assertDontSee($regular->title);

        $this->get(route('jobs.search', ['company' => 'verified search']))
            ->assertOk()
            ->assertSee($verified->title)
            ->assertDontSee($blacklisted->title)
            ->assertDontSee($regular->title);

        $this->get(route('jobs.search', [
            'q' => 'Laravel Verified',
            'company_type' => 'verified',
            'location' => 'Remote',
            'salary' => 'provided',
            'salary_text' => '150,000',
        ]))
            ->assertOk()
            ->assertSee($verified->title)
            ->assertDontSee($blacklisted->title)
            ->assertDontSee($regular->title);
    }

    public function test_job_search_result_actions_preserve_the_search_and_update_company_and_job_status(): void
    {
        $listing = $this->createListing('Actionable Search Role', 'https://example.com/actionable-search', now(), now());
        $company = Company::findOrCreateByName('Actionable Company');
        $listing->company()->associate($company)->save();
        $searchUrl = route('jobs.search', ['company' => 'Actionable']);

        $this->get($searchUrl)
            ->assertOk()
            ->assertSee('Whitelist')
            ->assertSee('Verify')
            ->assertSee('Blacklist')
            ->assertSee('Mark applied')
            ->assertSee('Mark not interested');

        $this->from($searchUrl)
            ->post(route('jobs.company-preference', $listing), ['type' => 'whitelist'])
            ->assertRedirect($searchUrl);
        $this->assertDatabaseHas('company_preferences', [
            'company_id' => $company->id,
            'type' => 'whitelist',
        ]);

        $this->from($searchUrl)
            ->post(route('jobs.status', $listing), ['status' => 'applied'])
            ->assertRedirect($searchUrl);
        $this->assertDatabaseHas('job_listings', [
            'id' => $listing->id,
            'status' => 'applied',
        ]);
    }
}
