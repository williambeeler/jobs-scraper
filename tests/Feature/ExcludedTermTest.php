<?php

namespace Tests\Feature;

use App\Models\ExcludedTerm;
use Illuminate\Support\Carbon;

class ExcludedTermTest extends FeatureTestCase
{
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
}
