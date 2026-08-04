<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;

class DashboardTest extends FeatureTestCase
{
    public function test_dashboard_shows_a_time_appropriate_greeting_and_three_job_dates(): void
    {
        Carbon::setTestNow('2026-08-04 14:00:00 UTC');

        $this->createListing(
            title: 'Today job',
            url: 'https://example.com/jobs/today-dashboard',
            lastSeenAt: Carbon::parse('2026-08-04 15:00:00 UTC'),
            createdAt: Carbon::parse('2026-08-04 15:00:00 UTC'),
        );
        $this->createListing(
            title: 'Yesterday job one',
            url: 'https://example.com/jobs/yesterday-dashboard-one',
            lastSeenAt: Carbon::parse('2026-08-03 15:00:00 UTC'),
            createdAt: Carbon::parse('2026-08-03 15:00:00 UTC'),
        );
        $this->createListing(
            title: 'Yesterday job two',
            url: 'https://example.com/jobs/yesterday-dashboard-two',
            lastSeenAt: Carbon::parse('2026-08-03 16:00:00 UTC'),
            createdAt: Carbon::parse('2026-08-03 16:00:00 UTC'),
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('Good morning, William!')
            ->assertSee('Tuesday, August 4')
            ->assertSee('Monday, August 3')
            ->assertSee('Sunday, August 2')
            ->assertSee('1 job')
            ->assertSee('2 jobs')
            ->assertSee(route('jobs.index'), false)
            ->assertSee(route('jobs.index', ['date' => '2026-08-03']), false)
            ->assertSee(route('jobs.index', ['date' => '2026-08-02']), false);
    }

    public function test_dashboard_uses_an_afternoon_greeting(): void
    {
        Carbon::setTestNow('2026-08-04 20:00:00 UTC');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Good afternoon, William!');
    }
}
