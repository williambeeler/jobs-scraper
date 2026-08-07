<?php

namespace Tests\Feature;

use App\Enums\KanbanStatus;
use App\Models\Company;
use App\Models\CompanyPreference;
use App\Models\JobListing;
use Illuminate\Support\Carbon;

class KanbanTest extends FeatureTestCase
{
    public function test_rejected_jobs_can_be_cleared_from_the_kanban_without_being_deleted(): void
    {
        $rejected = $this->createListing(
            title: 'Rejected Kanban Job',
            url: 'https://example.com/jobs/rejected-kanban',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );
        $rejected->update(['kanban_status' => KanbanStatus::Rejected]);

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSee($rejected->title)
            ->assertSee(route('jobs.kanban.rejected.clear'), false)
            ->assertSee('>Clear<', false);

        $this->post(route('jobs.kanban.rejected.clear'))
            ->assertRedirect(route('jobs.kanban'))
            ->assertSessionHas('status', '1 rejected job cleared from the Kanban.');

        $this->assertDatabaseHas('job_listings', [
            'id' => $rejected->id,
            'kanban_status' => 'rejected_cleared',
        ]);
        $this->assertDatabaseHas('job_listing_kanban_transitions', [
            'job_listing_id' => $rejected->id,
            'from_status' => 'rejected',
            'to_status' => 'rejected_cleared',
        ]);

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertDontSee($rejected->title);
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
        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertSee('data-not-interested', false)
            ->assertSee(route('jobs.status', $active), false)
            ->assertDontSee($hidden->title);
    }

    public function test_kanban_includes_an_asynchronous_job_and_company_search(): void
    {
        $listing = $this->createListing(
            title: 'WordPress Platform Engineer',
            url: 'https://example.com/jobs/wordpress-platform',
            lastSeenAt: now(),
            createdAt: now(),
        );
        $listing->company()->associate(Company::findOrCreateByName('Publishing Technology'))->save();

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSee('id="kanban-job-search"', false)
            ->assertSee('data-kanban-search', false)
            ->assertSee('placeholder="Search jobs or companies..."', false)
            ->assertSee('id="kanban-board"', false)
            ->assertSee('WordPress Platform Engineer')
            ->assertSee('Publishing Technology')
            ->assertSee('kanban-', false);
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
        $regular->company()->associate(Company::findOrCreateByName('Regular Company'))->save();

        $whitelisted = $this->createListing(
            title: 'Older Whitelisted Backlog Job',
            url: 'https://example.com/jobs/whitelisted-backlog',
            lastSeenAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
            createdAt: Carbon::parse('2026-07-22 12:00:00 UTC'),
        );
        CompanyPreference::query()->create([
            'company_id' => $whitelisted->company_id,
            'type' => 'whitelist',
        ]);

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSeeInOrder([$whitelisted->title, $regular->title]);
    }

    public function test_verified_company_jobs_use_the_blue_highlight_on_the_kanban(): void
    {
        $listing = $this->createListing(
            title: 'Verified Kanban Job',
            url: 'https://example.com/jobs/verified-kanban',
            lastSeenAt: now(),
            createdAt: now(),
        );
        CompanyPreference::query()->create([
            'company_id' => $listing->company_id,
            'type' => 'verified',
        ]);

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('kanban-card kanban-card-verified', false);
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
        ])->assertRedirect(route('jobs.kanban'))
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

        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSeeInOrder(['Interested', $listing->title, 'Applied To'])
            ->assertSee('data-listing-origin="custom"', false)
            ->assertDontSee('Not Interested');

        $this->post(route('jobs.excluded-terms.store'), ['term' => 'Referral'])
            ->assertRedirect(route('jobs.excluded-terms.index'));
        $this->post(route('jobs.company-preference', $listing), ['type' => 'blacklist'])
            ->assertRedirect();

        $this->assertNull($listing->fresh()->status);
        $this->get(route('jobs.kanban'))
            ->assertOk()
            ->assertSee($listing->title);

        $this->get(route('jobs.index', ['date' => '2026-07-22']))
            ->assertOk()
            ->assertDontSee($listing->title);

        $this->getJson(route('jobs.calendar.counts', [
            'start' => '2026-07-01',
            'end' => '2026-08-01',
        ]))->assertOk()->assertExactJson([]);

        $this->postJson(route('jobs.kanban-status', $listing), [
            'kanban_status' => 'applied',
        ])->assertOk();

        $this->get(route('jobs.applied-to'))
            ->assertOk()
            ->assertDontSee($listing->title);
    }
}
