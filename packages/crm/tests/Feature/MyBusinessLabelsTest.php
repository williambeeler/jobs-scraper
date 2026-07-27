<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use WilliamBeeler\Crm\Models\BusinessProfile;

class MyBusinessLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_labels_pdf_downloads_for_selected_business(): void
    {
        $business = BusinessProfile::query()->create([
            'business_name' => 'WilliamBeeler',
            'street_line_1' => '123 Main St',
            'street_line_2' => 'Suite 200',
            'city' => 'Tampa',
            'state' => 'FL',
            'postal_code' => '33602',
            'country' => 'USA',
            'is_default' => true,
        ]);

        $response = $this->get(route('my-business.labels.print', ['business_id' => $business->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition');
        $response->assertSee('%PDF-1.4', false);
    }
}
