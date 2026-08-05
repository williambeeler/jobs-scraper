<?php

namespace App\Console\Commands\Crm;

use App\Crm\Database\Seeders\RelationshipSeeder;
use App\Crm\Database\Seeders\StatusSeeder;
use App\Crm\Models\Company;
use App\Crm\Models\Relationship;
use App\Crm\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CompaniesRemapStatusesCommand extends Command
{
    protected $signature = 'companies:remap-statuses';

    protected $description = 'Map legacy company statuses into the new status and relationship model.';

    public function handle(): int
    {
        (new StatusSeeder)->run();
        (new RelationshipSeeder)->run();

        $statusBySlug = Status::query()->get()->keyBy('slug');
        $relationshipBySlug = Relationship::query()->get()->keyBy('slug');

        $legacyMappings = [
            'Lead' => ['status' => 'new', 'relationship' => 'lead'],
            'Prospect' => ['status' => 'new', 'relationship' => 'prospect'],
            'Call back - ASAP' => ['status' => 'new', 'relationship' => 'lead', 'callback_days' => 7],
            'Active' => ['status' => 'new', 'relationship' => 'lead'],
            'On Hold' => ['status' => 'on-hold', 'relationship' => 'lead'],
            'In Progress' => ['status' => 'in-progress', 'relationship' => 'lead'],
            'Closed / Rejected' => ['status' => 'closed-lost', 'relationship' => 'lead'],
            'Customer' => ['status' => 'closed-won', 'relationship' => 'customer'],
            'Partner' => ['status' => 'closed-won', 'relationship' => 'partner'],
            'Possible Client or IT Staffing' => ['status' => 'closed-won', 'relationship' => 'partner'],
            'Call back - 3 months' => ['status' => 'new', 'relationship' => 'lead', 'callback_days' => 7],
            'Call back - 6 months' => ['status' => 'new', 'relationship' => 'lead', 'callback_days' => 7],
        ];

        $defaultStatus = $statusBySlug->get('new');
        $defaultRelationship = $relationshipBySlug->get('lead');

        if (! $defaultStatus || ! $defaultRelationship) {
            $this->error('Required seeded statuses or relationships are missing.');

            return self::FAILURE;
        }

        $updated = 0;

        Company::query()
            ->with('status')
            ->chunkById(200, function (Collection $companies) use ($legacyMappings, $statusBySlug, $relationshipBySlug, $defaultStatus, $defaultRelationship, &$updated) {
                foreach ($companies as $company) {
                    $legacyName = $company->status?->name;
                    $mapping = $legacyMappings[$legacyName] ?? ['status' => 'new', 'relationship' => 'lead'];

                    $company->status_id = $statusBySlug->get($mapping['status'], $defaultStatus)->id;
                    $company->relationship_id = $relationshipBySlug->get($mapping['relationship'], $defaultRelationship)->id;
                    $company->needs_callback = array_key_exists('callback_days', $mapping);
                    $company->callback_at = array_key_exists('callback_days', $mapping)
                        ? now()->addDays((int) $mapping['callback_days'])
                        : null;
                    $company->save();

                    $updated++;
                }
            });

        Status::query()
            ->whereNotIn('slug', [
                'new',
                'contacted',
                'qualified',
                'proposal',
                'in-progress',
                'on-hold',
                'closed-won',
                'closed-lost',
            ])
            ->delete();

        $this->info("Remapped {$updated} companies to the new status and relationship structure.");

        return self::SUCCESS;
    }
}
