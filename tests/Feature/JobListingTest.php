<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JobListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_stored_job_listings(): void
    {
        $this->get(route('job-listings.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_filter_stored_job_listings(): void
    {
        $user = User::factory()->create();
        $matching = $this->createListing([
            'source' => 'LinkedIn',
            'title' => 'Senior WordPress Developer',
        ]);
        $this->createListing([
            'source' => 'Indeed',
            'title' => 'Laravel Developer',
            'url' => 'https://example.com/jobs/2',
        ]);

        $this->actingAs($user)
            ->get(route('job-listings.index', ['search' => 'WordPress', 'source' => 'LinkedIn']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('job-listings/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $matching->id)
                ->where('filters.search', 'WordPress')
                ->where('filters.source', 'LinkedIn'));
    }

    public function test_authenticated_users_can_view_a_stored_job_listing(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListing();

        $this->actingAs($user)
            ->get(route('job-listings.show', $listing))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('job-listings/Show')
                ->where('listing.id', $listing->id)
                ->where('listing.title', $listing->title));
    }

    public function test_scrape_command_rejects_an_unknown_source(): void
    {
        $this->artisan('jobs:scrape', ['--source' => ['monster']])
            ->expectsOutputToContain('Unknown source(s): monster.')
            ->assertExitCode(Command::INVALID);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createListing(array $attributes = []): JobListing
    {
        $url = (string) ($attributes['url'] ?? 'https://example.com/jobs/1');

        return JobListing::query()->create(array_merge([
            'source' => 'LinkedIn',
            'title' => 'WordPress Developer',
            'company' => 'Acme',
            'location' => 'Remote',
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'posted_at' => now()->toDateString(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ], $attributes));
    }
}
