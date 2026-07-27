<?php

namespace WilliamBeeler\Crm\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader;
use WilliamBeeler\Crm\Models\Company;
use WilliamBeeler\Crm\Support\TableNames;

class CompanyImportService
{
    /**
     * @var array<int, string>
     */
    private const IGNORED_COLUMNS = [
        'id',
        'uuid',
        'meta',
        'opportunity_score',
        'opportunity_score_index',
        'opportunity_summary',
    ];

    /**
     * @param  array{
     *     tags: array<int, string>,
     *     campaigns: array<int, string>,
     *     source: ?string,
     *     status_id: int,
     *     relationship_id: int
     * }  $options
     * @param  callable(array{
     *     type: string,
     *     total?: int,
     *     processed?: int,
     *     imported?: int,
     *     skipped?: int,
     *     message?: string,
     *     errors?: array<int, string>
     * }) : void  $emit
     */
    public function importFromCsv(UploadedFile $file, array $options, callable $emit): void
    {
        $path = $file->getRealPath();

        if ($path === false) {
            $emit([
                'type' => 'error',
                'message' => 'The uploaded file could not be read.',
            ]);

            return;
        }

        $total = $this->countRecords($path);

        $emit([
            'type' => 'start',
            'total' => $total,
            'processed' => 0,
            'imported' => 0,
            'skipped' => 0,
            'message' => 'Upload received. Starting import...',
        ]);

        if ($total === 0) {
            $emit([
                'type' => 'complete',
                'total' => 0,
                'processed' => 0,
                'imported' => 0,
                'skipped' => 0,
                'message' => 'The CSV did not contain any company rows to import.',
            ]);

            return;
        }

        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $importableColumns = $this->importableColumns();
        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($csv->getRecords() as $record) {
            $processed++;

            $attributes = $this->mapRecordToAttributes($record, $importableColumns);
            $attributes = $this->sanitizeAttributes($attributes);
            $attributes['source'] = $this->resolveSource($attributes['source'] ?? null, $options['source']);
            $attributes['status_id'] = $this->resolveRelatedId(
                $attributes['status_id'] ?? null,
                $options['status_id'],
                'statuses'
            );
            $attributes['relationship_id'] = $this->resolveRelatedId(
                $attributes['relationship_id'] ?? null,
                $options['relationship_id'],
                'relationships'
            );

            $validation = Validator::make($attributes, $this->validationRules());

            if ($validation->fails()) {
                $skipped++;
                $errors[] = 'Row '.$processed.' skipped: '.$validation->errors()->first();

                $emit([
                    'type' => 'progress',
                    'total' => $total,
                    'processed' => $processed,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'message' => "Imported {$imported} of {$total} companies...",
                ]);

                continue;
            }

            $validated = array_merge($attributes, $validation->validated());
            $validated['uuid'] = (string) Str::uuid();
            $validated['needs_callback'] = (bool) ($validated['needs_callback'] ?? false);

            if (! $validated['needs_callback']) {
                $validated['callback_at'] = null;
            }

            $company = Company::query()->create($validated);

            foreach ($options['tags'] as $tag) {
                $company->attachTag($tag, 'tag');
            }

            foreach ($options['campaigns'] as $campaign) {
                $company->attachTag($campaign, 'campaign');
            }

            $imported++;

            $emit([
                'type' => 'progress',
                'total' => $total,
                'processed' => $processed,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => "Imported {$imported} of {$total} companies...",
            ]);
        }

        $emit([
            'type' => 'complete',
            'total' => $total,
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
            'message' => "Import complete. {$imported} compan".($imported === 1 ? 'y' : 'ies').' imported.',
        ]);
    }

    private function countRecords(string $path): int
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        return iterator_count($csv->getRecords());
    }

    /**
     * @return array<int, string>
     */
    private function importableColumns(): array
    {
        return array_values(array_diff(
            Schema::getColumnListing(TableNames::get('companies')),
            [
                ...self::IGNORED_COLUMNS,
                'created_at',
                'updated_at',
                'deleted_at',
            ]
        ));
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $importableColumns
     * @return array<string, mixed>
     */
    private function mapRecordToAttributes(array $record, array $importableColumns): array
    {
        $attributes = [];

        foreach ($record as $column => $value) {
            $normalizedColumn = $this->normalizeColumnName((string) $column);

            if (! in_array($normalizedColumn, $importableColumns, true)) {
                continue;
            }

            $attributes[$normalizedColumn] = $this->castValue($normalizedColumn, $value);
        }

        return $attributes;
    }

    private function normalizeColumnName(string $column): string
    {
        return (string) Str::of($column)
            ->trim()
            ->lower()
            ->replace(['-', ' '], '_')
            ->replace('__', '_');
    }

    private function castValue(string $column, mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmedValue = trim($value);

        if ($trimmedValue === '') {
            return null;
        }

        if ($column === 'needs_callback') {
            return filter_var($trimmedValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if ($column === 'callback_at') {
            try {
                return Carbon::parse($trimmedValue);
            } catch (\Throwable) {
                return null;
            }
        }

        if (in_array($column, ['status_id', 'relationship_id'], true)) {
            if (! is_numeric($trimmedValue)) {
                return null;
            }

            return (int) $trimmedValue;
        }

        return $trimmedValue;
    }

    private function resolveSource(?string $rowSource, ?string $selectedSource): ?string
    {
        if (filled($selectedSource)) {
            return trim($selectedSource);
        }

        if (! filled($rowSource)) {
            return null;
        }

        return trim($rowSource);
    }

    private function resolveRelatedId(mixed $rowValue, int $fallbackId, string $table): int
    {
        if (! is_int($rowValue)) {
            return $fallbackId;
        }

        $table = TableNames::get($table);

        if (! Schema::hasTable($table)) {
            return $fallbackId;
        }

        if (! \DB::table($table)->where('id', $rowValue)->exists()) {
            return $fallbackId;
        }

        return $rowValue;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'needs_callback' => ['nullable', 'boolean'],
            'callback_at' => ['nullable', 'date'],
            'status_id' => ['nullable', 'integer'],
            'relationship_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function sanitizeAttributes(array $attributes): array
    {
        $limits = [
            'name' => 255,
            'alias' => 255,
            'website' => 255,
            'email' => 255,
            'phone' => 50,
            'poc_name' => 150,
            'poc_email' => 150,
            'poc_phone' => 50,
            'address_line1' => 255,
            'address_line2' => 255,
            'city' => 100,
            'state' => 100,
            'postal_code' => 20,
            'country' => 100,
            'source' => 100,
        ];

        foreach ($limits as $column => $limit) {
            if (! array_key_exists($column, $attributes)) {
                continue;
            }

            $attributes[$column] = $this->truncateString($attributes[$column], $limit);
        }

        return $attributes;
    }

    private function truncateString(mixed $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        return mb_substr($stringValue, 0, $limit);
    }
}
