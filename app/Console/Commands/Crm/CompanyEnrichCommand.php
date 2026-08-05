<?php

namespace App\Console\Commands\Crm;

use App\Crm\Models\Company;
use App\Crm\Services\Companies\CompanyAiEnrichmentService;
use Illuminate\Console\Command;

class CompanyEnrichCommand extends Command
{
    protected $signature = 'companies:enrich
        {company_id : Company ID to enrich}
        {--provider=openai : Prism provider}
        {--model=gpt-4.1-mini : Model to use}
        {--website-candidate= : Manually provide a preferred website candidate URL}
        {--overwrite : Overwrite existing non-empty fields}
        {--dry-run : Show suggestions without saving}';

    protected $description = 'Use AI via Prism to enrich one company record by ID.';

    public function handle(CompanyAiEnrichmentService $enrichmentService): int
    {
        $companyId = (int) $this->argument('company_id');
        $company = Company::query()->find($companyId);

        if (! $company) {
            $this->error("Company {$companyId} not found.");

            return self::FAILURE;
        }

        $provider = (string) $this->option('provider');
        $model = (string) $this->option('model');
        $websiteCandidate = $this->option('website-candidate');
        $websiteCandidate = is_string($websiteCandidate) ? trim($websiteCandidate) : null;
        if ($websiteCandidate === '') {
            $websiteCandidate = null;
        }
        $overwrite = (bool) $this->option('overwrite');
        $persist = ! $this->option('dry-run');

        try {
            $result = $enrichmentService->enrichCompany(
                company: $company,
                provider: $provider,
                model: $model,
                manualWebsiteCandidate: $websiteCandidate,
                overwrite: $overwrite,
                persist: $persist
            );
        } catch (\Throwable $e) {
            $this->error('AI enrichment failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $candidateList = $result['website_candidates'] ?? [];
        $this->line('Website candidates discovered:');
        if (empty($candidateList)) {
            $this->line('- (none)');
        } else {
            foreach ($candidateList as $candidate) {
                $this->line('- '.$candidate);
            }
        }
        $this->newLine();

        $usage = $result['usage'] ?? [];
        if (! empty($usage)) {
            $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
            $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
            $this->line("Token usage: input={$promptTokens}, output={$completionTokens}");

            if (isset($result['cost_estimate_usd']) && is_numeric($result['cost_estimate_usd'])) {
                $this->line(sprintf(
                    'Estimated API cost: $%0.6f (estimate)',
                    (float) $result['cost_estimate_usd']
                ));
            } else {
                $this->line('Estimated API cost: unavailable for this provider/model.');
            }

            $this->newLine();
        }

        $this->line('Suggested fields from AI:');
        $displayOrder = [
            'website',
            'phone',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'country',
            'email',
            'notes',
        ];

        $suggestedRows = [];
        foreach ($displayOrder as $field) {
            $value = $result['suggested'][$field] ?? null;
            $suggestedRows[] = [
                'field' => $field,
                'value' => is_null($value)
                    ? '(no suggestion)'
                    : (is_scalar($value) ? (string) $value : json_encode($value)),
            ];
        }

        $this->table(
            ['field', 'value'],
            $suggestedRows
        );

        if (empty($result['applied']) && empty($result['cleanup'])) {
            $this->info('No changes were applied.');

            return self::SUCCESS;
        }

        if (! empty($result['applied'])) {
            $this->line('AI-applied field changes:');
            $this->table(
                ['field', 'from', 'to'],
                collect($result['applied'])
                    ->map(fn ($change, $field) => [
                        'field' => $field,
                        'from' => (string) ($change['from'] ?? ''),
                        'to' => (string) ($change['to'] ?? ''),
                    ])
                    ->values()
                    ->all()
            );
        }

        if (! empty($result['cleanup'])) {
            $this->line('Post-enrichment cleanup changes:');
            $this->table(
                ['field', 'from', 'to'],
                collect($result['cleanup'])
                    ->map(fn ($change, $field) => [
                        'field' => $field,
                        'from' => (string) ($change['from'] ?? ''),
                        'to' => (string) ($change['to'] ?? ''),
                    ])
                    ->values()
                    ->all()
            );
        }

        $this->info($persist ? 'Enrichment complete and saved.' : 'Dry run complete (no changes saved).');

        return self::SUCCESS;
    }
}
