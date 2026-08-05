<?php

namespace Tests\Feature\Crm;

use App\Crm\Models\Company;
use App\Crm\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyTagsBadgeRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_tags_partial_renders_filter_link_badges(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Badge Company',
            'status_id' => $status->id,
        ]);
        $company->attachTag('sbc', 'tag');

        $html = view('crm.companies.partials.tags', ['company' => $company])->render();

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('tag_filter=sbc', $html);
        $this->assertStringContainsString('badge rounded-pill crm-tag-badge', $html);
        $this->assertStringContainsString('sbc', $html);
    }

    public function test_company_status_partial_renders_filter_link_badge(): void
    {
        $status = Status::factory()->create([
            'name' => 'Lead',
            'color' => '#198754',
        ]);
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Status Badge Company',
            'status_id' => $status->id,
        ]);

        $html = view('crm.companies.partials.status', ['company' => $company])->render();

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('status_id='.$status->id, $html);
        $this->assertStringContainsString('Lead', $html);
        $this->assertStringContainsString('background-color: #198754', $html);
    }

    public function test_company_campaigns_partial_renders_campaign_filter_badges(): void
    {
        $status = Status::factory()->create();
        $company = Company::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Campaign Badge Company',
            'status_id' => $status->id,
        ]);
        $company->attachTag('Brochure Spring 2026', 'campaign');

        $html = view('crm.companies.partials.campaigns', ['company' => $company])->render();

        $this->assertStringContainsString('campaign_filter=Brochure%20Spring%202026', $html);
        $this->assertStringContainsString('Brochure Spring 2026', $html);
        $this->assertStringContainsString('badge rounded-pill bg-info', $html);
    }
}
