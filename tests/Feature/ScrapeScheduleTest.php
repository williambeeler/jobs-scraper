<?php

namespace Tests\Feature;

use App\Models\ScrapeSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

class ScrapeScheduleTest extends FeatureTestCase
{
    public function test_scrape_configuration_can_be_saved_and_listed(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => 'wordpress developer remote',
            'sources' => ['linkedin', 'dice'],
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ])->assertRedirect(route('jobs.cron'))
            ->assertSessionHas('status', 'Scrape configuration saved.');

        $this->assertDatabaseHas('scrape_schedules', [
            'keywords' => 'wordpress developer remote',
            'max_days' => 5,
            'interval_hours' => 6,
            'is_active' => true,
        ]);

        $this->get(route('jobs.cron'))
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

        $this->post(route('jobs.cron.store'), $configuration)
            ->assertRedirect(route('jobs.cron'));

        $this->post(route('jobs.cron.store'), [
            ...$configuration,
            'sources' => ['dice', 'linkedin'],
            'interval_hours' => 12,
        ])->assertRedirect(route('jobs.cron'))
            ->assertSessionHas('status', 'Scrape configuration updated.');

        $this->assertDatabaseCount('scrape_schedules', 1);
        $this->assertDatabaseHas('scrape_schedules', [
            'keywords' => 'laravel remote',
            'interval_hours' => 12,
        ]);
    }

    public function test_full_board_sources_can_be_saved_in_a_scrape_configuration(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => '',
            'sources' => ['wordpress_jobs', 'larajobs'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('jobs.cron'));

        $this->get(route('jobs.cron'))
            ->assertOk()
            ->assertSee('WordPress Jobs')
            ->assertSee('LaraJobs');
    }

    public function test_keyword_search_sources_still_require_keywords(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => '',
            'sources' => ['linkedin'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertSessionHasErrors('keywords');
    }

    public function test_working_nomads_can_be_saved_as_a_keyword_source(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => 'wordpress remote',
            'sources' => ['working_nomads'],
            'max_days' => 7,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('jobs.cron'));

        $this->get(route('jobs.cron'))
            ->assertOk()
            ->assertSee('Working Nomads');
    }

    public function test_christian_tech_jobs_can_be_saved_as_a_keyword_source(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => 'software remote',
            'sources' => ['christian_tech_jobs'],
            'max_days' => 30,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('jobs.cron'));

        $this->get(route('jobs.cron'))
            ->assertOk()
            ->assertSee('Christian Tech Jobs');
    }

    public function test_caci_careers_can_be_saved_as_a_keyword_source(): void
    {
        $this->post(route('jobs.cron.store'), [
            'keywords' => 'support engineer',
            'sources' => ['caci_careers'],
            'max_days' => 30,
            'interval_hours' => 12,
            'is_active' => true,
        ])->assertRedirect(route('jobs.cron'));

        $this->get(route('jobs.cron'))
            ->assertOk()
            ->assertSee('CACI Careers');
    }

    public function test_cron_page_lists_configurations_before_the_form(): void
    {
        $this->get(route('jobs.cron'))
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

        $this->get(route('jobs.cron.edit', $schedule))
            ->assertOk()
            ->assertSee('Edit configuration')
            ->assertSee('value="wordpress remote"', false)
            ->assertSee('Update configuration');

        $this->put(route('jobs.cron.update', $schedule), [
            'keywords' => 'laravel remote',
            'sources' => ['dice', 'working_nomads'],
            'max_days' => 3,
            'interval_hours' => 12,
            'is_active' => false,
        ])->assertRedirect(route('jobs.cron'))
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

        $this->delete(route('jobs.cron.destroy', $schedule))
            ->assertRedirect(route('jobs.cron'))
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

        $this->post(route('jobs.cron.schedule.run', $schedule))
            ->assertRedirect(route('jobs.cron'))
            ->assertSessionHas('status', 'Scrape configuration completed.');

        $schedule->refresh();
        $this->assertTrue($schedule->last_ran_at?->equalTo(now()));
        $this->assertTrue($schedule->next_run_at?->equalTo(now()->addHours(6)));
    }

    public function test_cron_run_endpoint_requires_the_configured_bearer_token(): void
    {
        config(['services.scrape_cron.token' => 'test-cron-token']);

        $this->get(route('jobs.cron.run'))->assertForbidden();
        $this->withToken('wrong-token')->get(route('jobs.cron.run'))->assertForbidden();
        $this->withToken('test-cron-token')->get(route('jobs.cron.run'))->assertNoContent();
    }

    public function test_schedule_runner_succeeds_when_no_configurations_are_due(): void
    {
        $this->artisan('jobs:run-schedules')
            ->expectsOutput('No scrape configurations are due.')
            ->assertSuccessful();
    }
}
