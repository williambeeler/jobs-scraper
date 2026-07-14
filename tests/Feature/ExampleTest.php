<?php

namespace Tests\Feature;

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

        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('Monday, July 13, 2026')
            ->assertSee('value="2026-07-13"', false)
            ->assertSee($todaysListing->title)
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

        $this->get(route('home', ['date' => '2026-07-12']))
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

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Monday, July 13, 2026')
            ->assertSee('value="2026-07-13"', false)
            ->assertSee($listing->title)
            ->assertSee('First scraped: 7:51 PM');
    }

    public function test_home_page_has_an_empty_state_when_no_jobs_were_scraped_today(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No jobs were first scraped on July 13, 2026.');
    }

    public function test_public_navigation_pages_are_available(): void
    {
        $this->get(route('cron'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('Cron')
            ->assertSee('Applied to');

        $this->get(route('applied-to'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('Cron')
            ->assertSee('Applied to');
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
