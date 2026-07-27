<?php

namespace WilliamBeeler\Crm\Exports;

use Generator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use WilliamBeeler\Crm\Support\TableNames;

class CompaniesTableExport implements FromGenerator, WithHeadings
{
    private const CACHE_TTL_SECONDS = 86400;

    private const PROGRESS_BATCH_SIZE = 25;

    /**
     * @param  array<int, string>  $columns
     */
    public function __construct(
        private readonly array $columns,
        private readonly string $exportId,
        private readonly int $total
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->columns;
    }

    public function generator(): Generator
    {
        foreach ($this->companyRows() as $row) {
            yield $row;
        }
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    private function companyRows(): Generator
    {
        $companies = DB::table(TableNames::get('companies'))
            ->select($this->columns)
            ->orderBy('id')
            ->lazyById(250);

        $processed = 0;

        foreach ($companies as $company) {
            $processed++;

            if ($processed % self::PROGRESS_BATCH_SIZE === 0 || $processed === $this->total) {
                $this->updateProgress($processed);
            }

            yield $this->mapCompanyRow($company);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function mapCompanyRow(object $company): array
    {
        $row = [];

        foreach ($this->columns as $column) {
            $row[] = $this->normalizeValue($company->{$column} ?? null);
        }

        return $row;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value) || is_object($value)) {
            $json = json_encode($value);

            return $json === false ? '' : $json;
        }

        return $value;
    }

    private function updateProgress(int $processed): void
    {
        $cacheKey = 'company-export:'.$this->exportId;
        $existing = Cache::get($cacheKey);

        if (! is_array($existing)) {
            return;
        }

        Cache::put(
            $cacheKey,
            array_merge($existing, [
                'processed' => $processed,
            ]),
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );
    }
}
