<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyUpdateTagsPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_persists_selected_tags_and_campaigns(): void
    {
        $status = Status::factory()->create();

        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Acme Roofing',
            'status_id' => $status->id,
        ]);

        $company->attachTag('Legacy Tag', 'tag');
        $company->attachTag('Legacy Campaign', 'campaign');

        $response = $this->put(route('companies.update', $company), [
            'name' => 'Acme Roofing',
            'status_id' => $status->id,
            'tags' => 'Priority, Follow Up',
            'campaigns' => 'Spring 2026, Retarget',
        ]);

        $response->assertRedirect(route('companies.show', $company));

        $fresh = $company->fresh();
        $this->assertSame(
            ['Follow Up', 'Priority'],
            $fresh->tagsWithType('tag')->pluck('name')->sort()->values()->all()
        );
        $this->assertSame(
            ['Retarget', 'Spring 2026'],
            $fresh->tagsWithType('campaign')->pluck('name')->sort()->values()->all()
        );
    }
}
