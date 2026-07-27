<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;

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

        $this->get(route('statuses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Statuses/Index')
                ->where('statuses.data.0.name', 'Qualified')
                ->where('relationships.data.0.name', 'Lead'));

        $this->get(route('statuses.show', $status))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Statuses/Show')
                ->where('status.name', 'Qualified'));
    }
}
