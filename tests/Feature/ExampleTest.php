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
            ->assertSee($todaysListing->title)
            ->assertDontSee($oldListing->title);
    }

    public function test_home_page_has_an_empty_state_when_no_jobs_were_scraped_today(): void
    {
        Carbon::setTestNow('2026-07-13 12:00:00');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No jobs have been scraped today yet.');
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
