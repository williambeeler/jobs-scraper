<?php

namespace App\Crm\Database\Seeders;

use App\Crm\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'New', 'slug' => 'new', 'description' => 'Just entered the CRM. No action taken yet.', 'color' => '#6c757d', 'is_default' => true],
            ['name' => 'Contacted', 'slug' => 'contacted', 'description' => 'Initial outreach has occurred.', 'color' => '#0d6efd'],
            ['name' => 'Qualified', 'slug' => 'qualified', 'description' => 'Confirmed potential project or need.', 'color' => '#198754'],
            ['name' => 'Proposal', 'slug' => 'proposal', 'description' => 'Quote, proposal, or pricing sent.', 'color' => '#0dcaf0'],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'description' => 'Work or negotiations actively happening.', 'color' => '#20c997'],
            ['name' => 'On Hold', 'slug' => 'on-hold', 'description' => 'Temporarily paused by either party.', 'color' => '#ffc107'],
            ['name' => 'Closed Won', 'slug' => 'closed-won', 'description' => 'Project accepted / customer acquired.', 'color' => '#198754'],
            ['name' => 'Closed Lost', 'slug' => 'closed-lost', 'description' => 'Opportunity rejected or declined.', 'color' => '#dc3545'],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
