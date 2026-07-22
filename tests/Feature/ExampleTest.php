<?php

namespace Tests\Feature;

use App\Models\CompanyPreference;
use App\Models\ExcludedTerm;
use App\Models\JobListing;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_only_jobs_scraped_today(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $todaysListing = $this->createListing(
            title: 'Today WordPress Developer',
            url: 'https://example.com/jobs/today',
            lastSeenAt: now(),
            createdAt: now(),
        );
        $oldListing = $this->createListing(
            title: 'Yesterday Laravel Developer',
            url: 'https://example.com/jobs/yesterday',
            lastSeenAt: now(),
            createdAt: now()->subDay(),
        );

        $this->get(route('jobs'))
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('Monday, July 13, 2026')
            ->assertSee('value="2026-07-13"', false)
            ->assertSee($todaysListing->title)
            ->assertSee('Not Interested')
            ->assertSee('Applied')
            ->assertSee('data-job-status-form', false)
            ->assertSee('fetch(form.action', false)
            ->assertDontSee($oldListing->title);
    }

    public function test_home_page_can_list_jobs_created_on_a_selected_date(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $todaysListing = $this->createListing(
            title: 'Today WordPress Developer',
            url: 'https://example.com/jobs/today',
            lastSeenAt: now(),
            createdAt: now(),
        );
        $selectedDateListing = $this->createListing(
            title: 'Sunday Laravel Developer',
            url: 'https://example.com/jobs/sunday',
            lastSeenAt: now(),
            createdAt: now()->subDay(),
        );

        $this->get(route('jobs', ['date' => '2026-07-12']))
            ->assertOk()
            ->assertSee('Sunday, July 12, 2026')
            ->assertSee('value="2026-07-12"', false)
            ->assertSee($selectedDateListing->title)
            ->assertDontSee($todaysListing->title);
    }

    public function test_home_page_uses_chicago_date_after_utc_has_advanced_to_the_next_day(): void
    {
        Carbon::setTestNow('2026-07-14 00:51:00 UTC');

        $listing = $this->createListing(
            title: 'Evening WordPress Developer',
            url: 'https://example.com/jobs/evening',
            lastSeenAt: now(),
            createdAt: now(),
        );

        $this->get(route('jobs'))
            ->assertOk()
            ->assertSee('Monday, July 13, 2026')
            ->assertSee('value="2026-07-13"', false)
            ->assertSee($listing->title)
            ->assertSee('First scraped: 7:51 PM');
    }

    public function test_home_page_has_an_empty_state_when_no_jobs_were_scraped_today(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $this->get(route('jobs'))
            ->assertOk()
            ->assertSee('No jobs were first scraped on July 13, 2026.');
    }

    public function test_public_navigation_pages_are_available(): void
    {
        $this->get(route('cron'))
            ->assertOk()
            ->assertSee('Job Scraper')
            ->assertSee('navbar navbar-expand bg-white', false)
            ->assertSee('data-bs-toggle="dropdown"', false)
            ->assertSeeInOrder(['Tools', 'Applied to', 'Settings', 'Companies List', 'Cron', 'Excluded terms'])
            ->assertSee(route('company-preferences.index'), false)
            ->assertSee('aria-label="Home"', false)
            ->assertSee('aria-label="Settings"', false);

        $this->get(route('applied-to'))
            ->assertOk()
            ->assertSee('Job Scraper')
            ->assertSee('aria-label="Jobs"', false)
            ->assertSee('aria-label="Bible"', false);
    }

    public function test_settings_backup_page_has_backup_and_restore_navigation(): void
    {
        $this->get(route('settings.backup'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Create database backup')
            ->assertSee('Previous backups')
            ->assertSee(route('settings.restore'), false);

        $this->get(route('settings.restore'))
            ->assertOk()
            ->assertSee('Restore controls will be added here');
    }

    public function test_scrape_configuration_can_be_saved_and_listed(): void
    {
        $this->post(route('cron.store'), [
            'keywords' => 'wordpress developer remote',
            'sources' => ['linkedin', 'indeed'],
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ])->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration saved.');

        $this->assertDatabaseHas('scrape_schedules', [
            'keywords' => 'wordpress developer remote',
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ]);

        $this->get(route('cron'))
            ->assertOk()
            ->assertSee('wordpress developer remote')
            ->assertSee('Linkedin')
            ->assertSee('Indeed')
            ->assertSee('Every 6 hours');
    }

    public function test_saving_the_same_keyword_and_source_combination_updates_it(): void
    {
        $configuration = [
            'keywords' => 'laravel remote',
            'sources' => ['linkedin', 'indeed'],
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ];

        $this->post(route('cron.store'), $configuration)
            ->assertRedirect(route('cron'));

        $this->post(route('cron.store'), [
            ...$configuration,
            'sources' => ['indeed', 'linkedin'],
            'interval_hours' => 12,
        ])->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration updated.');

        $this->assertDatabaseCount('scrape_schedules', 1);
        $this->assertDatabaseHas('scrape_schedules', [
            'keywords' => 'laravel remote',
            'interval_hours' => 12,
        ]);
    }

    public function test_cron_run_endpoint_requires_the_configured_bearer_token(): void
    {
        config(['services.scrape_cron.token' => 'test-cron-token']);

        $this->get(route('cron.run'))->assertForbidden();
        $this->withToken('wrong-token')->get(route('cron.run'))->assertForbidden();
        $this->withToken('test-cron-token')->get(route('cron.run'))->assertNoContent();
    }

    public function test_schedule_runner_succeeds_when_no_configurations_are_due(): void
    {
        $this->artisan('jobs:run-schedules')
            ->expectsOutput('No scrape configurations are due.')
            ->assertSuccessful();
    }

    public function test_marking_a_job_as_applied_hides_it_from_home_and_lists_it_on_applied_page(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00 UTC');
        $listing = $this->createListing(
            title: 'Applied WordPress Developer',
            url: 'https://example.com/jobs/applied',
            lastSeenAt: now(),
            createdAt: now(),
        );

        $listing->update(['company_key' => JobListing::companyKey($listing->company)]);
        CompanyPreference::query()->create([
            'company_name' => $listing->company,
            'company_key' => $listing->company_key,
            'type' => 'whitelist',
        ]);

        $this->postJson(route('jobs.status', $listing), ['status' => 'applied'])
            ->assertOk()
            ->assertJson(['status' => 'applied']);

        $this->assertDatabaseHas('job_listings', [
            'id' => $listing->id,
            'status' => 'applied',
        ]);

        $this->get(route('jobs'))
            ->assertOk()
            ->assertDontSee($listing->title);

        $this->get(route('applied-to'))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('Applied: Jul 13, 2026 7:00 AM')
            ->assertSee('job-row-whitelisted', false)
            ->assertSee('aria-label="Whitelisted company"', false)
            ->assertDontSee('Whitelist company');
    }

    public function test_marking_a_job_not_interested_hides_it_without_listing_it_as_applied(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00 UTC');
        $listing = $this->createListing(
            title: 'Declined WordPress Developer',
            url: 'https://example.com/jobs/declined',
            lastSeenAt: now(),
            createdAt: now(),
        );

        $this->post(route('jobs.status', $listing), ['status' => 'not_interested'])
            ->assertRedirect(route('home'));

        $this->assertDatabaseHas('job_listings', [
            'id' => $listing->id,
            'status' => 'not_interested',
        ]);

        $this->get(route('jobs'))->assertDontSee($listing->title);
        $this->get(route('applied-to'))->assertDontSee($listing->title);
    }

    public function test_applied_jobs_are_grouped_into_lists_by_the_local_application_date(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00 UTC');

        $newer = $this->createListing(
            title: 'Monday Application',
            url: 'https://example.com/jobs/monday-application',
            lastSeenAt: Carbon::parse('2026-07-13 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-13 12:00:00 UTC'),
        );
        $older = $this->createListing(
            title: 'Sunday Application',
            url: 'https://example.com/jobs/sunday-application',
            lastSeenAt: Carbon::parse('2026-07-12 16:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-12 16:00:00 UTC'),
        );

        $newer->update(['status' => 'applied', 'status_changed_at' => '2026-07-13 12:00:00 UTC']);
        $older->update(['status' => 'applied', 'status_changed_at' => '2026-07-12 16:00:00 UTC']);

        $response = $this->get(route('applied-to'))
            ->assertOk()
            ->assertSeeInOrder([
                'Monday, July 13, 2026',
                '7 days ago',
                $newer->title,
                'Sunday, July 12, 2026',
                '8 days ago',
                $older->title,
            ]);

        $this->assertSame(2, substr_count($response->getContent(), 'class="list-group shadow-sm"'));
    }

    public function test_excluded_terms_hide_matching_jobs_and_mark_them_not_interested(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00 UTC');
        $matching = $this->createListing(
            title: 'Senior .NET Developer',
            url: 'https://example.com/jobs/dotnet',
            lastSeenAt: now(),
            createdAt: now(),
        );
        $other = $this->createListing(
            title: 'Laravel Developer',
            url: 'https://example.com/jobs/laravel',
            lastSeenAt: now(),
            createdAt: now(),
        );

        $this->post(route('excluded-terms.store'), ['term' => '.NET'])
            ->assertRedirect(route('excluded-terms.index'));

        $this->assertDatabaseHas('excluded_terms', ['term' => '.NET', 'term_key' => '.net']);
        $this->assertDatabaseHas('job_listings', ['id' => $matching->id, 'status' => 'not_interested']);
        $this->get(route('jobs'))->assertDontSee($matching->title)->assertSee($other->title);
        $this->get(route('excluded-terms.index'))->assertOk()->assertSee('Excluded terms (1)');
    }

    public function test_excluded_terms_can_be_edited_and_deleted(): void
    {
        $term = ExcludedTerm::query()->create(['term' => '.NET', 'term_key' => '.net']);

        $this->put(route('excluded-terms.update', $term), ['term' => 'Java'])
            ->assertRedirect(route('excluded-terms.index'));
        $this->assertDatabaseHas('excluded_terms', ['id' => $term->id, 'term' => 'Java', 'term_key' => 'java']);

        $this->delete(route('excluded-terms.destroy', $term))
            ->assertRedirect(route('excluded-terms.index'));
        $this->assertDatabaseMissing('excluded_terms', ['id' => $term->id]);
    }

    private function createListing(
        string $title,
        string $url,
        CarbonInterface $lastSeenAt,
        CarbonInterface $createdAt,
    ): JobListing {
        $listing = new JobListing([
            'source' => 'LinkedIn',
            'title' => $title,
            'company' => 'Acme',
            'location' => 'Remote',
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'posted_at' => $lastSeenAt->toDateString(),
            'first_seen_at' => $lastSeenAt,
            'last_seen_at' => $lastSeenAt,
        ]);
        $listing->created_at = $createdAt;
        $listing->updated_at = $lastSeenAt;
        $listing->save();

        return $listing;
    }
}
