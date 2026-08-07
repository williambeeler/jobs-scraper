<?php

namespace Tests\Feature;

use App\Models\CompanyPreference;
use Illuminate\Support\Carbon;

class JobStatusTest extends FeatureTestCase
{
    public function test_marking_a_job_as_applied_hides_it_from_home_and_lists_it_on_applied_page(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00 UTC');
        $listing = $this->createListing(
            title: 'Applied WordPress Developer',
            url: 'https://example.com/jobs/applied',
            lastSeenAt: now(),
            createdAt: now(),
        );

        CompanyPreference::query()->create([
            'company_id' => $listing->company_id,
            'type' => 'whitelist',
        ]);

        $this->postJson(route('jobs.status', $listing), ['status' => 'applied'])
            ->assertOk()
            ->assertJson(['status' => 'applied']);

        $this->assertDatabaseHas('job_listings', [
            'id' => $listing->id,
            'status' => 'applied',
        ]);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertDontSee($listing->title);

        $this->get(route('jobs.applied-to'))
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

        $this->get(route('jobs.index'))->assertDontSee($listing->title);
        $this->get(route('jobs.applied-to'))->assertDontSee($listing->title);
    }

    public function test_applied_jobs_from_verified_companies_use_the_blue_highlight(): void
    {
        $listing = $this->createListing(
            title: 'Verified Applied Job',
            url: 'https://example.com/jobs/verified-applied',
            lastSeenAt: now(),
            createdAt: now(),
        );
        $listing->update(['status' => 'applied', 'status_changed_at' => now()]);
        CompanyPreference::query()->create([
            'company_id' => $listing->company_id,
            'type' => 'verified',
        ]);

        $this->get(route('jobs.applied-to'))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('list-group-item p-4 job-row-verified', false);
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

        $response = $this->get(route('jobs.applied-to'))
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
}
