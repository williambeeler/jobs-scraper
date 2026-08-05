<?php

namespace Tests\Feature;

use App\Models\JobListing;

class JobListingDetailsTest extends FeatureTestCase
{
    public function test_company_name_links_to_a_google_search_for_company_and_location(): void
    {
        $listing = $this->createListing('Research Role', 'https://example.com/research-role', now(), now());
        $expectedUrl = 'https://www.google.com/search?q=Acme+Remote';

        $this->assertSame($expectedUrl, $listing->company_search_url);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('href="'.$expectedUrl.'" target="_blank" rel="noopener noreferrer"', false);
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
        $this->get(route('jobs.index'))
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
}
