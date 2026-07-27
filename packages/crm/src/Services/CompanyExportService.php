<?php

namespace WilliamBeeler\Crm\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use WilliamBeeler\Crm\Exports\CompaniesTableExport;
use WilliamBeeler\Crm\Support\TableNames;

class CompanyExportService
{
    public const TYPE_COMPANIES_CSV_ALL = 'companies_csv_all';

    public const TYPE_COMPANIES_XLSX_ALL = 'companies_xlsx_all';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    private const CACHE_TTL_SECONDS = 86400;

    private const EXPORT_DISK = 'local';

    private const EXPORT_DIRECTORY = 'exports/companies';

    public function countCompanies(): int
    {
        return (int) DB::table(TableNames::get('companies'))->count();
    }

    /**
     * @return array<int, array{value: string, label: string, total: int}>
     */
    public function getExportOptions(): array
    {
        return [
            [
                'value' => self::TYPE_COMPANIES_CSV_ALL,
                'label' => 'Export All Businesses as CSV',
                'total' => $this->countCompanies(),
            ],
            [
                'value' => self::TYPE_COMPANIES_XLSX_ALL,
                'label' => 'Export All Businesses as XLSX',
                'total' => $this->countCompanies(),
            ],
        ];
    }

    /**
     * @return array{id: string, total: int, label: string}
     */
    public function createExport(string $type): array
    {
        $option = collect($this->getExportOptions())
            ->first(static fn (array $candidate): bool => $candidate['value'] === $type);

        if ($option === null) {
            abort(422, 'Unsupported export type.');
        }

        $exportId = (string) Str::uuid();
        $total = (int) $option['total'];

        $this->putStatus($exportId, [
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'total' => $total,
            'processed' => 0,
            'filename' => null,
            'path' => null,
            'token' => Str::random(40),
            'error' => null,
        ]);

        return [
            'id' => $exportId,
            'total' => $total,
            'label' => $option['label'],
        ];
    }

    public function buildExport(string $exportId): void
    {
        $status = $this->getExportStatus($exportId);

        if ($status === null) {
            return;
        }

        if (($status['type'] ?? null) === self::TYPE_COMPANIES_CSV_ALL) {
            $this->buildAllCompaniesExport($exportId, ExcelWriter::CSV);

            return;
        }

        if (($status['type'] ?? null) === self::TYPE_COMPANIES_XLSX_ALL) {
            $this->buildAllCompaniesExport($exportId, ExcelWriter::XLSX);

            return;
        }

        $this->putStatus($exportId, [
            'status' => self::STATUS_FAILED,
            'error' => 'Unsupported export type.',
        ]);
    }

    private function buildAllCompaniesExport(string $exportId, string $writerType): void
    {
        $status = $this->getExportStatus($exportId);

        if ($status === null) {
            return;
        }

        $columns = Schema::getColumnListing(TableNames::get('companies'));
        $timestamp = now()->format('Ymd_His');
        $extension = $this->extensionForWriterType($writerType);
        $filename = "companies_export_{$timestamp}_{$exportId}.{$extension}";
        $relativePath = self::EXPORT_DIRECTORY.'/'.$filename;

        $this->putStatus($exportId, [
            'filename' => $filename,
            'path' => $relativePath,
            'status' => self::STATUS_RUNNING,
            'processed' => 0,
            'error' => null,
        ]);

        try {
            $stored = Excel::store(
                new CompaniesTableExport($columns, $exportId, (int) $status['total']),
                $relativePath,
                self::EXPORT_DISK,
                $writerType
            );

            if (! $stored) {
                throw new \RuntimeException('Unable to create the export file.');
            }

            $processed = $status['total'];

            $this->putStatus($exportId, [
                'status' => self::STATUS_COMPLETED,
                'processed' => $processed,
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            if (Storage::disk(self::EXPORT_DISK)->exists($relativePath ?? '')) {
                Storage::disk(self::EXPORT_DISK)->delete($relativePath);
            }

            $this->putStatus($exportId, [
                'status' => self::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{type: string, status: string, total: int, processed: int, filename: ?string, path: ?string, token: string, error: ?string}|null
     */
    public function getExportStatus(string $exportId): ?array
    {
        $status = Cache::get($this->cacheKey($exportId));

        return is_array($status) ? $status : null;
    }

    public function downloadExport(string $exportId, string $token): StreamedResponse
    {
        $status = $this->getExportStatus($exportId);

        if (
            $status === null
            || $status['status'] !== self::STATUS_COMPLETED
            || ! hash_equals((string) $status['token'], $token)
            || empty($status['path'])
            || ! Storage::disk(self::EXPORT_DISK)->exists($status['path'])
        ) {
            abort(404);
        }

        return Storage::disk(self::EXPORT_DISK)->download($status['path'], $status['filename'] ?? basename($status['path']));
    }

    private function cacheKey(string $exportId): string
    {
        return 'company-export:'.$exportId;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function putStatus(string $exportId, array $attributes): void
    {
        $existing = $this->getExportStatus($exportId) ?? [];

        Cache::put(
            $this->cacheKey($exportId),
            array_merge($existing, $attributes),
            now()->addSeconds(self::CACHE_TTL_SECONDS)
        );
    }

    private function extensionForWriterType(string $writerType): string
    {
        if ($writerType === ExcelWriter::CSV) {
            return 'csv';
        }

        if ($writerType === ExcelWriter::XLSX) {
            return 'xlsx';
        }

        throw new \InvalidArgumentException('Unsupported writer type.');
    }
}
