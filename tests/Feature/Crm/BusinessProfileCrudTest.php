<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\BusinessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProfileCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_profiles_can_be_listed_created_and_updated(): void
    {
        $this->get(route('crm.my-business.index'))
            ->assertOk()
            ->assertViewIs('crm.my-business.index')
            ->assertViewHas('mode', 'create');

        $this->post(route('crm.my-business.address.store'), $this->profileData([
            'business_name' => 'First Business',
        ]))->assertRedirect(route('crm.my-business.index', ['tab' => 'address']));

        $business = BusinessProfile::query()->sole();
        $this->assertTrue($business->is_default);

        $this->patch(route('crm.my-business.address.update', $business), $this->profileData([
            'business_name' => 'Updated Business',
        ]))->assertRedirect();

        $this->assertDatabaseHas($business->getTable(), [
            'id' => $business->id,
            'business_name' => 'Updated Business',
        ]);
    }

    public function test_deleting_the_default_profile_promotes_another_profile(): void
    {
        $default = BusinessProfile::query()->create($this->profileData([
            'business_name' => 'Default Business',
            'is_default' => true,
        ]));
        $replacement = BusinessProfile::query()->create($this->profileData([
            'business_name' => 'Replacement Business',
            'is_default' => false,
        ]));

        $this->delete(route('crm.my-business.destroy', $default))
            ->assertRedirect(route('crm.my-business.index', ['tab' => 'address']));

        $this->assertModelMissing($default);
        $this->assertTrue($replacement->fresh()->is_default);
    }

    private function profileData(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'WilliamBeeler',
            'street_line_1' => '123 Main St',
            'street_line_2' => null,
            'city' => 'Tampa',
            'state' => 'FL',
            'postal_code' => '33602',
            'country' => 'USA',
            'is_default' => false,
        ], $overrides);
    }
}
