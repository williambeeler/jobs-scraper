<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\Relationship;
use App\Crm\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_statuses_and_relationships_are_rendered_by_the_package(): void
    {
        $status = Status::factory()->create(['name' => 'Qualified']);
        Relationship::query()->create([
            'name' => 'Lead',
            'slug' => 'lead',
            'description' => 'Potential customer',
        ]);

        $this->get(route('crm.statuses.index'))
            ->assertOk()
            ->assertViewIs('crm.statuses.index')
            ->assertViewHas('statuses', fn (array $statuses) => $statuses['data'][0]['name'] === 'Qualified')
            ->assertViewHas('relationships', fn (array $relationships) => $relationships['data'][0]['name'] === 'Lead');

        $this->get(route('crm.statuses.show', $status))
            ->assertOk()
            ->assertViewIs('crm.statuses.show')
            ->assertViewHas('status', fn (array $status) => $status['name'] === 'Qualified');
    }
}
