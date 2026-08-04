<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Models\Status;

class CompanyIndexDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_index_uses_the_application_list_card_design(): void
    {
        $status = Status::factory()->create(['name' => 'Lead']);
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Acme Company',
            'status_id' => $status->id,
            'city' => 'Chicago',
            'state' => 'IL',
        ]);

        $this->get(route('crm.companies.index'))
            ->assertOk()
            ->assertSee('list-group shadow-sm', false)
            ->assertSee('list-group-item p-4', false)
            ->assertSee('Acme Company')
            ->assertSee('Chicago, IL')
            ->assertSee(route('crm.companies.edit', $company), false);
    }
}
