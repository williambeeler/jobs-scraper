<?php

namespace Tests\Feature;

use App\Models\CompanyPreference;
use App\Models\ExcludedTerm;
use App\Models\JobListing;
use App\Models\ScrapeSchedule;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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

    public function test_posted_age_is_calculated_from_posted_at_and_cached(): void
    {
        Carbon::setTestNow('2026-07-27 12:00:00');
        Cache::flush();
        $listing = $this->createListing(
            title: 'Cached Date Developer',
            url: 'https://example.com/jobs/cached-date',
            lastSeenAt: now()->subDays(2),
            createdAt: now(),
        );

        $this->assertSame('2 days ago', $listing->posted_age);
        $this->assertTrue(Cache::has(
            "job-listing:{$listing->id}:posted-age:{$listing->posted_at->toDateString()}",
        ));
        $this->assertFalse(Schema::hasColumn('job_listings', 'posted_relative'));
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
            ->assertDontSee('First scraped');
    }

    public function test_home_page_has_an_empty_state_when_no_jobs_were_scraped_today(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $this->get(route('jobs'))
            ->assertOk()
            ->assertSee('No jobs were first scraped on July 13, 2026.');
    }

    public function test_calendar_counts_are_limited_to_the_requested_visible_range(): void
    {
        $whitelisted = $this->createListing(
            title: 'Whitelisted July Job',
            url: 'https://example.com/jobs/whitelisted-july',
            lastSeenAt: Carbon::parse('2026-07-14 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-14 12:00:00 UTC'),
        );
        $whitelisted->update(['company_key' => JobListing::companyKey($whitelisted->company)]);
        CompanyPreference::query()->create([
            'company_name' => $whitelisted->company,
            'company_key' => $whitelisted->company_key,
            'type' => 'whitelist',
        ]);

        $this->createListing(
            title: 'Another July Job',
            url: 'https://example.com/jobs/another-july',
            lastSeenAt: Carbon::parse('2026-07-14 15:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-14 15:00:00 UTC'),
        );
        $this->createListing(
            title: 'August Job',
            url: 'https://example.com/jobs/august',
            lastSeenAt: Carbon::parse('2026-08-02 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-08-02 12:00:00 UTC'),
        );

        $this->getJson(route('calendar.counts', [
            'start' => '2026-07-01',
            'end' => '2026-08-01',
        ]))->assertOk()->assertExactJson([
            ['date' => '2026-07-14', 'total' => 2, 'whitelisted' => 1],
        ]);
    }

    public function test_active_jobs_default_to_the_kanban_backlog_and_not_interested_jobs_are_hidden(): void
    {
        $active = $this->createListing(
            title: 'Active Kanban Job',
            url: 'https://example.com/jobs/active-kanban',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );
        $hidden = $this->createListing(
            title: 'Hidden Kanban Job',
            url: 'https://example.com/jobs/hidden-kanban',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );
        $hidden->update(['status' => 'not_interested']);

        $this->assertSame('backlog', $active->fresh()->kanban_status->value);
        $this->assertSame('scraped', $active->fresh()->listing_origin->value);
        $this->get(route('kanban'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertSee('data-not-interested', false)
            ->assertSee(route('jobs.status', $active), false)
            ->assertDontSee($hidden->title);
    }

    public function test_moving_a_kanban_job_to_applied_updates_both_statuses(): void
    {
        $listing = $this->createListing(
            title: 'Kanban Application',
            url: 'https://example.com/jobs/kanban-application',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );

        $this->postJson(route('jobs.kanban-status', $listing), [
            'kanban_status' => 'applied',
        ])->assertOk()->assertJson([
            'kanban_status' => 'applied',
            'status' => 'applied',
        ]);

        $listing->refresh();
        $this->assertSame('applied', $listing->kanban_status->value);
        $this->assertSame('applied', $listing->status->value);

        $this->postJson(route('jobs.kanban-status', $listing), [
            'kanban_status' => 'interview',
        ])->assertOk();

        $listing->refresh();
        $this->assertSame('interview', $listing->kanban_status->value);
        $this->assertSame('applied', $listing->status->value);
        $this->assertNotNull($listing->kanban_status_changed_at);
        $this->assertDatabaseHas('job_listing_kanban_transitions', [
            'job_listing_id' => $listing->id,
            'from_status' => 'backlog',
            'to_status' => 'applied',
        ]);
        $this->assertDatabaseHas('job_listing_kanban_transitions', [
            'job_listing_id' => $listing->id,
            'from_status' => 'applied',
            'to_status' => 'interview',
        ]);
        $this->assertDatabaseCount('job_listing_kanban_transitions', 2);
    }

    public function test_kanban_backlog_lists_whitelisted_company_jobs_first(): void
    {
        $regular = $this->createListing(
            title: 'Newer Regular Backlog Job',
            url: 'https://example.com/jobs/regular-backlog',
            lastSeenAt: Carbon::parse('2026-07-22 14:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 14:00:00 UTC'),
        );
        $regular->update([
            'company' => 'Regular Company',
            'company_key' => JobListing::companyKey('Regular Company'),
        ]);

        $whitelisted = $this->createListing(
            title: 'Older Whitelisted Backlog Job',
            url: 'https://example.com/jobs/whitelisted-backlog',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );
        $whitelisted->update(['company_key' => JobListing::companyKey($whitelisted->company)]);
        CompanyPreference::query()->create([
            'company_name' => $whitelisted->company,
            'company_key' => $whitelisted->company_key,
            'type' => 'whitelist',
        ]);

        $this->get(route('kanban'))
            ->assertOk()
            ->assertSeeInOrder([$whitelisted->title, $regular->title]);
    }

    public function test_custom_jobs_start_interested_and_only_appear_on_the_kanban(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00 UTC');

        $this->post(route('jobs.custom.store'), [
            'title' => 'Custom Referral Opportunity',
            'company' => 'Referral Company',
            'location' => 'Remote',
            'url' => 'https://example.com/jobs/custom-referral',
            'source' => 'Referral',
            'posted_at' => '2026-07-21',
            'salary' => '$120,000',
        ])->assertRedirect(route('kanban'))
            ->assertSessionHas('status', 'Custom job added.');

        $listing = JobListing::query()->where('title', 'Custom Referral Opportunity')->firstOrFail();

        $this->assertSame('custom', $listing->listing_origin->value);
        $this->assertSame('interested', $listing->kanban_status->value);
        $this->assertNull($listing->status);
        $this->assertDatabaseHas('job_listing_kanban_transitions', [
            'job_listing_id' => $listing->id,
            'from_status' => 'backlog',
            'to_status' => 'interested',
        ]);

        $this->get(route('kanban'))
            ->assertOk()
            ->assertSeeInOrder(['Interested', $listing->title, 'Applied To'])
            ->assertSee('data-listing-origin="custom"', false)
            ->assertDontSee('Not Interested');

        $this->post(route('excluded-terms.store'), ['term' => 'Referral'])
            ->assertRedirect(route('excluded-terms.index'));
        $this->post(route('jobs.company-preference', $listing), ['type' => 'blacklist'])
            ->assertRedirect();

        $this->assertNull($listing->fresh()->status);
        $this->get(route('kanban'))
            ->assertOk()
            ->assertSee($listing->title);

        $this->get(route('jobs', ['date' => '2026-07-22']))
            ->assertOk()
            ->assertDontSee($listing->title);

        $this->getJson(route('calendar.counts', [
            'start' => '2026-07-01',
            'end' => '2026-08-01',
        ]))->assertOk()->assertExactJson([]);

        $this->postJson(route('jobs.kanban-status', $listing), [
            'kanban_status' => 'applied',
        ])->assertOk();

        $this->get(route('applied-to'))
            ->assertOk()
            ->assertDontSee($listing->title);
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

        $this->get(route('kanban'))
            ->assertOk()
            ->assertSeeInOrder([
                'Backlog',
                'Interested',
                'Applied To',
                'Interview',
                'Job Won',
                'Rejected',
            ])
            ->assertSee('data-kanban-column', false);
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
            'sources' => ['linkedin', 'dice'],
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
            ->assertSee('LinkedIn')
            ->assertSee('Dice')
            ->assertSee('Every 6 hours');
    }

    public function test_saving_the_same_keyword_and_source_combination_updates_it(): void
    {
        $configuration = [
            'keywords' => 'laravel remote',
            'sources' => ['linkedin', 'dice'],
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ];

        $this->post(route('cron.store'), $configuration)
            ->assertRedirect(route('cron'));

        $this->post(route('cron.store'), [
            ...$configuration,
            'sources' => ['dice', 'linkedin'],
            'interval_hours' => 12,
        ])->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration updated.');

        $this->assertDatabaseCount('scrape_schedules', 1);
        $this->assertDatabaseHas('scrape_schedules', [
            'keywords' => 'laravel remote',
            'interval_hours' => 12,
        ]);
    }

    public function test_full_board_sources_can_be_saved_in_a_scrape_configuration(): void
    {
        $this->post(route('cron.store'), [
            'keywords' => '',
            'sources' => ['wordpress_jobs', 'larajobs'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('cron'));

        $this->get(route('cron'))
            ->assertOk()
            ->assertSee('WordPress Jobs')
            ->assertSee('LaraJobs');
    }

    public function test_keyword_search_sources_still_require_keywords(): void
    {
        $this->post(route('cron.store'), [
            'keywords' => '',
            'sources' => ['linkedin'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertSessionHasErrors('keywords');
    }

    public function test_working_nomads_can_be_saved_as_a_keyword_source(): void
    {
        $this->post(route('cron.store'), [
            'keywords' => 'wordpress remote',
            'sources' => ['working_nomads'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('cron'));

        $this->get(route('cron'))
            ->assertOk()
            ->assertSee('Working Nomads');
    }

    public function test_cron_page_lists_configurations_before_the_form(): void
    {
        $this->get(route('cron'))
            ->assertOk()
            ->assertSeeInOrder(['Saved configurations', 'Add configuration']);
    }

    public function test_scrape_configuration_can_be_edited_and_updated(): void
    {
        $schedule = ScrapeSchedule::query()->create([
            'keywords' => 'wordpress remote',
            'sources' => ['linkedin'],
            'configuration_hash' => hash('sha256', 'wordpress remote|linkedin'),
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ]);

        $this->get(route('cron.edit', $schedule))
            ->assertOk()
            ->assertSee('Edit configuration')
            ->assertSee('value="wordpress remote"', false)
            ->assertSee('Update configuration');

        $this->put(route('cron.update', $schedule), [
            'keywords' => 'laravel remote',
            'sources' => ['dice', 'working_nomads'],
            'max_days' => 3,
            'interval_hours' => 12,
            'is_active' => false,
        ])->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration updated.');

        $this->assertDatabaseHas('scrape_schedules', [
            'id' => $schedule->id,
            'keywords' => 'laravel remote',
            'max_days' => 3,
            'interval_hours' => 12,
            'is_active' => false,
        ]);
    }

    public function test_scrape_configuration_can_be_deleted(): void
    {
        $schedule = ScrapeSchedule::query()->create([
            'keywords' => '',
            'sources' => ['larajobs'],
            'configuration_hash' => hash('sha256', '|larajobs'),
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ]);

        $this->delete(route('cron.destroy', $schedule))
            ->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration deleted.');

        $this->assertDatabaseMissing('scrape_schedules', ['id' => $schedule->id]);
    }

    public function test_scrape_configuration_can_be_run_manually(): void
    {
        Carbon::setTestNow('2026-07-27 10:30:00');
        $schedule = ScrapeSchedule::query()->create([
            'keywords' => 'laravel remote',
            'sources' => ['working_nomads'],
            'configuration_hash' => hash('sha256', 'laravel remote|working_nomads'),
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('jobs:scrape', [
                '--keywords' => 'laravel remote',
                '--days' => 5,
                '--source' => ['working_nomads'],
                '--schedule' => $schedule->id,
                '--no-interaction' => true,
            ])
            ->andReturn(0);

        $this->post(route('cron.schedule.run', $schedule))
            ->assertRedirect(route('cron'))
            ->assertSessionHas('status', 'Scrape configuration completed.');

        $schedule->refresh();
        $this->assertTrue($schedule->last_ran_at?->equalTo(now()));
        $this->assertTrue($schedule->next_run_at?->equalTo(now()->addHours(6)));
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
            $listing->update(['company' => $company, 'company_key' => JobListing::companyKey($company)]);
        }

        CompanyPreference::query()->create([
            'company_name' => $verified->company,
            'company_key' => $verified->company_key,
            'type' => 'verified',
        ]);
        CompanyPreference::query()->create([
            'company_name' => $whitelisted->company,
            'company_key' => $whitelisted->company_key,
            'type' => 'whitelist',
        ]);

        $this->get(route('jobs'))
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
        $listing->update(['company_key' => JobListing::companyKey($listing->company)]);

        $this->post(route('jobs.company-preference', $listing), ['type' => 'verified'])
            ->assertRedirect();

        $this->assertDatabaseHas('company_preferences', [
            'company_key' => $listing->company_key,
            'type' => 'verified',
        ]);
    }

    public function test_only_the_job_listing_salary_can_be_updated(): void
    {
        $listing = $this->createListing('Original Role', 'https://example.com/original-role', now(), now());

        $this->put(route('jobs.details.update', $listing), [
            'salary' => '$140,000',
        ])->assertRedirect()
            ->assertSessionHas('status', 'Salary updated.');

        $this->assertDatabaseHas('job_listings', [
            'id' => $listing->id,
            'title' => 'Original Role',
            'salary' => '$140,000',
        ]);
    }

    public function test_polymorphic_notes_can_be_added_to_and_removed_from_a_job_listing(): void
    {
        $listing = $this->createListing('Noted Role', 'https://example.com/noted-role', now(), now());

        $this->post(route('jobs.notes.store', $listing), [
            'body' => '<p onclick="alert(1)">Recruiter confirmed the <mark>salary range</mark>.</p><script>alert(1)</script>',
        ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Note added.');

        $note = $listing->notes()->firstOrFail();
        $this->assertSame(JobListing::class, $note->notable_type);
        $this->assertSame($listing->id, $note->notable_id);
        $this->assertSame('<p>Recruiter confirmed the <mark>salary range</mark>.</p>', $note->body);
        $this->get(route('jobs'))
            ->assertOk()
            ->assertSee('Edit details &amp; notes', false)
            ->assertSee('Recruiter confirmed the')
            ->assertSee('<mark>salary range</mark>', false)
            ->assertDontSee('onclick="alert(1)"', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->delete(route('jobs.notes.destroy', [$listing, $note]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Note deleted.');

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
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
