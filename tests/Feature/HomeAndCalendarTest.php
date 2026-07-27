<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyPreference;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HomeAndCalendarTest extends FeatureTestCase
{
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
        CompanyPreference::query()->create([
            'company_id' => $whitelisted->company_id,
            'type' => 'whitelist',
        ]);

        $anotherJuly = $this->createListing(
            title: 'Another July Job',
            url: 'https://example.com/jobs/another-july',
            lastSeenAt: Carbon::parse('2026-07-14 15:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-14 15:00:00 UTC'),
        );
        $anotherJuly->company()->associate(Company::findOrCreateByName('Another Company'))->save();
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
}
