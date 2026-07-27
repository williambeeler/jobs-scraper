<?php

namespace WilliamBeeler\Crm\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config as SpatieBackupConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use WilliamBeeler\Crm\Http\Requests\ImportCompaniesRequest;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;
use WilliamBeeler\Crm\Models\Tag;
use WilliamBeeler\Crm\Services\CompanyExportService;
use WilliamBeeler\Crm\Services\CompanyImportService;
use WilliamBeeler\Crm\Services\GoogleCloudPlatformService;
use WilliamBeeler\Crm\Services\OpenAiPlatformService;
use WilliamBeeler\Crm\Support\EnvValueStore;

class ToolsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $activeTab = $request->query('tab', 'backups');

        if ($activeTab !== 'backups') {
            $activeTab = 'backups';
        }

        return Inertia::render('Tools/Index', [
            'activeTab' => $activeTab,
            'backups' => $this->transformBackups($this->listBackups()),
            'isSpatieBackupInstalled' => $this->isSpatieBackupInstalled(),
        ]);
    }

    public function import(): InertiaResponse
    {
        return Inertia::render('Tools/Import', [
            'campaigns' => Tag::query()->where('type', 'campaign')->orderBy('name')->get(),
            'tags' => Tag::query()->where('type', 'tag')->orderBy('name')->get(),
            'statuses' => Status::query()->orderBy('name')->get(),
            'relationships' => Relationship::query()->orderBy('name')->get(),
            'defaultStatusId' => (int) Status::query()->where('is_default', true)->value('id'),
            'leadRelationshipId' => (int) Relationship::query()->where('slug', 'lead')->value('id'),
            'templateDownloadUrl' => route('tools.import.template'),
            'maxUploadSize' => $this->resolveMaxUploadSize(),
        ]);
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        return response()->download(
            __DIR__.'/../../../resources/import-templates/company-import-template.csv',
            'company-import-template.csv'
        );
    }

    public function importCompanies(
        ImportCompaniesRequest $request,
        CompanyImportService $companyImportService
    ): StreamedResponse {
        $validated = $request->validated();

        return response()->stream(function () use ($companyImportService, $request, $validated): void {
            ignore_user_abort(true);
            set_time_limit(0);

            $emit = function (array $payload): void {
                echo json_encode($payload)."\n";

                if (function_exists('ob_flush')) {
                    ob_flush();
                }

                flush();
            };

            try {
                $companyImportService->importFromCsv(
                    $request->file('file'),
                    [
                        'tags' => $this->normalizeTagValues($validated['tags'] ?? null),
                        'campaigns' => $this->normalizeTagValues($validated['campaigns'] ?? null),
                        'source' => filled($validated['source'] ?? null) ? trim((string) $validated['source']) : null,
                        'status_id' => (int) $validated['status_id'],
                        'relationship_id' => (int) $validated['relationship_id'],
                    ],
                    $emit
                );
            } catch (Throwable $e) {
                $emit([
                    'type' => 'error',
                    'message' => $e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function googlePlatform(): InertiaResponse
    {
        return Inertia::render('Tools/GooglePlatform', app(GoogleCloudPlatformService::class)->getDashboardData());
    }

    public function redirectGoogleCloudOAuth(): RedirectResponse
    {
        if (! filled(config('services.google.oauth_client_id')) || ! filled(config('services.google.oauth_client_secret'))) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'This CRM is missing its Google OAuth client configuration. Add GOOGLE_CLOUD_OAUTH_CLIENT_ID and GOOGLE_CLOUD_OAUTH_CLIENT_SECRET in the environment first.');
        }

        if (! filled(config('services.google.cloud_project_id'))) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'This CRM is missing GOOGLE_CLOUD_PROJECT_ID, so Google Cloud data cannot be mapped to the right project yet.');
        }

        $state = Str::random(40);
        session(['google_cloud_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google.oauth_client_id'),
            'redirect_uri' => route('tools.googlePlatform.oauth.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/cloud-platform.read-only',
                'https://www.googleapis.com/auth/monitoring.read',
                'https://www.googleapis.com/auth/cloud-billing.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function handleGoogleCloudOAuthCallback(
        Request $request,
        EnvValueStore $envValueStore,
        GoogleCloudPlatformService $googleCloudPlatformService
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
        ]);

        $expectedState = session('google_cloud_oauth_state');
        session()->forget('google_cloud_oauth_state');

        if (filled($validated['error'] ?? null)) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'Google authorization failed: '.$validated['error']);
        }

        if (! is_string($expectedState) || $expectedState === '' || ($validated['state'] ?? null) !== $expectedState) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'Google authorization state could not be verified.');
        }

        if (! filled($validated['code'] ?? null)) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'Google authorization did not return an authorization code.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(20)
                ->post('https://oauth2.googleapis.com/token', [
                    'code' => $validated['code'],
                    'client_id' => config('services.google.oauth_client_id'),
                    'client_secret' => config('services.google.oauth_client_secret'),
                    'redirect_uri' => route('tools.googlePlatform.oauth.callback'),
                    'grant_type' => 'authorization_code',
                ]);

            if (! $response->ok()) {
                $message = data_get($response->json(), 'error_description')
                    ?: data_get($response->json(), 'error')
                    ?: 'Google token exchange failed.';

                return redirect()
                    ->route('tools.googlePlatform')
                    ->with('error', $message);
            }

            $refreshToken = (string) $response->json('refresh_token');

            if ($refreshToken === '') {
                return redirect()
                    ->route('tools.googlePlatform')
                    ->with('error', 'Google did not return a refresh token. Try connecting again after revoking the previous grant.');
            }

            $envValueStore->set('GOOGLE_CLOUD_OAUTH_REFRESH_TOKEN', $refreshToken);
            config()->set('services.google.oauth_refresh_token', $refreshToken);
            $googleCloudPlatformService->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'Google OAuth callback failed: '.$e->getMessage());
        }

        return redirect()
            ->route('tools.googlePlatform')
            ->with('success', 'Google Cloud connected successfully.');
    }

    public function removeGoogleCloudCredentials(
        EnvValueStore $envValueStore,
        GoogleCloudPlatformService $googleCloudPlatformService
    ): RedirectResponse {
        try {
            $envValueStore->remove('GOOGLE_CLOUD_OAUTH_REFRESH_TOKEN');

            config()->set('services.google.oauth_refresh_token', null);
            $googleCloudPlatformService->clearCache();
            session()->forget('google_cloud_oauth_state');

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('tools.googlePlatform')
                ->with('error', 'Google Cloud credentials could not be removed: '.$e->getMessage());
        }

        return redirect()
            ->route('tools.googlePlatform')
            ->with('success', 'Google account disconnected.');
    }

    public function openAiPlatform(OpenAiPlatformService $openAiPlatformService): InertiaResponse
    {
        return Inertia::render('Tools/OpenAiPlatform', $openAiPlatformService->getDashboardData());
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTagValues(string|array|null $rawValues): array
    {
        if (is_array($rawValues)) {
            return collect($rawValues)
                ->map(fn (mixed $value): string => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (! filled($rawValues)) {
            return [];
        }

        return collect(explode(',', $rawValues))
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveMaxUploadSize(): string
    {
        $uploadMaxFilesize = $this->shorthandToBytes((string) ini_get('upload_max_filesize'));
        $postMaxSize = $this->shorthandToBytes((string) ini_get('post_max_size'));
        $limits = array_values(array_filter([$uploadMaxFilesize, $postMaxSize]));
        $maxUploadSize = $limits === [] ? 0 : min($limits);

        return $maxUploadSize > 0 ? Number::fileSize($maxUploadSize) : 'Server default';
    }

    private function shorthandToBytes(string $value): int
    {
        $trimmedValue = trim($value);

        if ($trimmedValue === '') {
            return 0;
        }

        $number = (int) $trimmedValue;
        $suffix = strtolower(substr($trimmedValue, -1));

        return match ($suffix) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    public function storeOpenAiUsageKey(Request $request, EnvValueStore $envValueStore): RedirectResponse
    {
        $validated = $request->validate([
            'usage_api_key' => ['required', 'string', 'min:20'],
        ]);

        try {
            $usageApiKey = trim($validated['usage_api_key']);

            $envValueStore->set('OPENAI_USAGE_API_KEY', $usageApiKey);
            config()->set('services.openai.usage_api_key', $usageApiKey);
            app(OpenAiPlatformService::class)->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('tools.openAiPlatform')
                ->with('error', 'OpenAI usage key could not be saved: '.$e->getMessage());
        }

        return redirect()
            ->route('tools.openAiPlatform')
            ->with('success', 'OpenAI usage key connected successfully.');
    }

    public function removeOpenAiUsageKey(EnvValueStore $envValueStore): RedirectResponse
    {
        try {
            $envValueStore->remove('OPENAI_USAGE_API_KEY');
            config()->set('services.openai.usage_api_key', null);
            app(OpenAiPlatformService::class)->clearCache();

            if (app()->configurationIsCached()) {
                Artisan::call('config:clear');
            }
        } catch (Throwable $e) {
            return redirect()
                ->route('tools.openAiPlatform')
                ->with('error', 'OpenAI usage key could not be removed: '.$e->getMessage());
        }

        return redirect()
            ->route('tools.openAiPlatform')
            ->with('success', 'OpenAI usage key removed.');
    }

    public function export(): InertiaResponse
    {
        $companyExportService = app(CompanyExportService::class);

        return Inertia::render('Tools/Export', [
            'companyCount' => $companyExportService->countCompanies(),
            'exportOptions' => $companyExportService->getExportOptions(),
        ]);
    }

    public function startExport(Request $request, CompanyExportService $companyExportService): JsonResponse
    {
        $validated = $request->validate([
            'export_type' => 'required|string',
        ]);

        $export = $companyExportService->createExport($validated['export_type']);

        app()->terminating(function () use ($companyExportService, $export): void {
            $companyExportService->buildExport($export['id']);
        });

        return response()->json([
            'ok' => true,
            'export_id' => $export['id'],
            'total' => $export['total'],
            'label' => $export['label'],
        ]);
    }

    public function exportStatus(string $exportId, CompanyExportService $companyExportService): JsonResponse
    {
        $status = $companyExportService->getExportStatus($exportId);

        if ($status === null) {
            abort(404);
        }

        return response()->json([
            'ok' => true,
            'status' => $status['status'],
            'total' => $status['total'],
            'processed' => $status['processed'],
            'filename' => $status['filename'],
            'error' => $status['error'],
            'download_url' => $status['status'] === CompanyExportService::STATUS_COMPLETED
                ? route('tools.exports.download', ['exportId' => $exportId, 'token' => $status['token']])
                : null,
        ]);
    }

    public function downloadExport(Request $request, string $exportId, CompanyExportService $companyExportService): StreamedResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        return $companyExportService->downloadExport($exportId, $validated['token']);
    }

    public function runDatabaseBackup(): RedirectResponse
    {
        if (! $this->isSpatieBackupInstalled()) {
            return redirect()
                ->route('tools.index', ['tab' => 'backups'])
                ->with('error', 'Spatie Laravel Backup is not installed. Run composer require spatie/laravel-backup.');
        }

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('tools.index', ['tab' => 'backups'])
                ->with('error', 'Backup failed: '.$e->getMessage());
        }

        if ($exitCode !== 0) {
            $output = trim(Artisan::output());
            $message = $this->extractBackupFailureMessage($output);

            return redirect()
                ->route('tools.index', ['tab' => 'backups'])
                ->with('error', $message);
        }

        return redirect()
            ->route('tools.index', ['tab' => 'backups'])
            ->with('success', 'Database backup started successfully.');
    }

    public function downloadBackup(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'disk' => 'required|string',
            'path' => 'required|string',
        ]);

        $disk = $validated['disk'];
        $path = $validated['path'];

        if (! array_key_exists($disk, config('filesystems.disks', []))) {
            abort(404);
        }

        if (! $this->backupExistsInList($disk, $path)) {
            abort(404);
        }

        return Storage::disk($disk)->download($path, basename($path));
    }

    /**
     * @return array<int, array{disk: string, path: string, size: int, last_modified: int}>
     */
    private function listBackups(): array
    {
        if ($this->isSpatieBackupInstalled()) {
            return $this->listSpatieBackups();
        }

        return $this->listFallbackZipBackups();
    }

    /**
     * @return array<int, array{disk: string, path: string, size: int, last_modified: int}>
     */
    private function listSpatieBackups(): array
    {
        /** @var SpatieBackupConfig $config */
        $config = app(SpatieBackupConfig::class);
        $backups = [];

        foreach (BackupDestinationFactory::createFromArray($config) as $destination) {
            $destinationBackups = $destination->backups()->reverse()->values();

            foreach ($destinationBackups as $backup) {
                $backups[] = [
                    'disk' => $destination->diskName(),
                    'path' => $backup->path(),
                    'size' => (int) $backup->sizeInBytes(),
                    'last_modified' => $backup->date()->timestamp,
                ];
            }
        }

        usort(
            $backups,
            static fn (array $a, array $b): int => $b['last_modified'] <=> $a['last_modified']
        );

        return $backups;
    }

    /**
     * @return array<int, array{disk: string, path: string, size: int, last_modified: int}>
     */
    private function listFallbackZipBackups(): array
    {
        $disks = (array) config('backup.backup.destination.disks', ['local']);
        $backups = [];

        foreach ($disks as $disk) {
            if (! is_string($disk) || $disk === '' || ! array_key_exists($disk, config('filesystems.disks', []))) {
                continue;
            }

            $storage = Storage::disk($disk);

            foreach ($storage->allFiles() as $path) {
                if (! str_ends_with(strtolower($path), '.zip')) {
                    continue;
                }

                $backups[] = [
                    'disk' => $disk,
                    'path' => $path,
                    'size' => $storage->size($path),
                    'last_modified' => $storage->lastModified($path),
                ];
            }
        }

        usort(
            $backups,
            static fn (array $a, array $b): int => $b['last_modified'] <=> $a['last_modified']
        );

        return $backups;
    }

    private function isSpatieBackupInstalled(): bool
    {
        return class_exists(\Spatie\Backup\Commands\BackupCommand::class);
    }

    private function backupExistsInList(string $disk, string $path): bool
    {
        foreach ($this->listBackups() as $backup) {
            if ($backup['disk'] === $disk && $backup['path'] === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{disk: string, path: string, size: int, last_modified: int}>  $backups
     * @return array<int, array{disk: string, path: string, size: int, size_label: string, last_modified: int, last_modified_label: string, download_url: string}>
     */
    private function transformBackups(array $backups): array
    {
        return collect($backups)
            ->map(function (array $backup): array {
                return [
                    ...$backup,
                    'size_label' => Number::fileSize($backup['size']),
                    'last_modified_label' => now()->createFromTimestamp($backup['last_modified'])->toDayDateTimeString(),
                    'download_url' => URL::temporarySignedRoute(
                        'tools.backups.download',
                        now()->addMinutes(30),
                        [
                            'disk' => $backup['disk'],
                            'path' => $backup['path'],
                        ]
                    ),
                ];
            })
            ->values()
            ->all();
    }

    private function extractBackupFailureMessage(string $output): string
    {
        if ($output === '') {
            return 'Backup failed. Check logs for details.';
        }

        if (preg_match('/Error Output\s*=+\s*(.+)$/s', $output, $matches) === 1) {
            $error = trim((string) $matches[1]);
            $firstLine = collect(preg_split('/\R+/', $error) ?: [])
                ->map(static fn (string $line): string => trim($line))
                ->first(static fn (string $line): bool => $line !== '');

            $singleLine = $firstLine ?: (preg_replace('/\s+/', ' ', $error) ?? $error);

            return 'Backup failed: '.Str::limit($singleLine, 220);
        }

        $singleLine = preg_replace('/\s+/', ' ', $output) ?? $output;

        return 'Backup failed: '.Str::limit($singleLine, 220);
    }
}
