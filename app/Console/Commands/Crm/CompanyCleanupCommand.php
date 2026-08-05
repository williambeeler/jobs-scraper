<?php

namespace App\Console\Commands\Crm;

use App\Crm\Models\Company;
use App\Crm\Services\Companies\CompanyDataCleanupService;
use Illuminate\Console\Command;

class CompanyCleanupCommand extends Command
{
    protected $signature = 'companies:cleanup
        {company_id : Company ID to clean}
        {--provider=openai : Prism provider}
        {--model=gpt-4.1-mini : Model to use}
        {--dry-run : Show changes without saving}';

    protected $description = 'Clean and normalize one company record by ID.';

    public function handle(CompanyDataCleanupService $cleanupService): int
    {
        $companyId = (int) $this->argument('company_id');
        $company = Company::query()->find($companyId);

        if (! $company) {
            $this->error("Company {$companyId} not found.");

            return self::FAILURE;
        }

        $persist = ! $this->option('dry-run');
        $provider = (string) $this->option('provider');
        $model = (string) $this->option('model');
        $changes = $cleanupService->cleanupCompany($company, $persist, $provider, $model);

        if (empty($changes)) {
            $this->info('No cleanup changes needed.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($changes as $field => $change) {
            $rows[] = [
                'field' => $field,
                'from' => (string) ($change['from'] ?? ''),
                'to' => (string) ($change['to'] ?? ''),
            ];
        }

        $this->table(['field', 'from', 'to'], $rows);
        $this->info($persist ? 'Cleanup saved.' : 'Dry run complete (no changes saved).');

        return self::SUCCESS;
    }
}
