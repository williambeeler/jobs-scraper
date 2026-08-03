<?php

namespace WilliamBeeler\Crm\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ToolsBackupsPageTest extends TestCase
{
    public function test_tools_page_loads_with_backups_tab(): void
    {
        $response = $this->get(route('crm.tools.index'));

        $response->assertOk();
        $response->assertViewIs('crm::tools.index');
        $response->assertViewHas('activeTab', 'backups');
        $response->assertViewHas('backups');
    }

    public function test_backup_action_runs_database_only_backup_command(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->withArgs(function (string $command, array $parameters): bool {
                return $command === 'backup:run'
                    && ($parameters['--only-db'] ?? false) === true
                    && ($parameters['--disable-notifications'] ?? false) === true;
            })
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->never();

        $response = $this->post(route('crm.tools.backups.run'));

        $response->assertRedirect(route('crm.tools.index', ['tab' => 'backups']));
        $response->assertSessionHas('success', 'Database backup started successfully.');
    }

    public function test_backup_action_returns_error_when_backup_command_fails(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn(
                "Starting backup...\nError Output\n============\nmysqldump: command not found"
            );

        $response = $this->post(route('crm.tools.backups.run'));

        $response->assertRedirect(route('crm.tools.index', ['tab' => 'backups']));
        $response->assertSessionHas('error');
    }

    public function test_backup_download_requires_signed_url(): void
    {
        $response = $this->get(route('crm.tools.backups.download', [
            'disk' => 'local',
            'path' => 'fake/file.zip',
        ]));

        $response->assertForbidden();
    }

    public function test_backup_download_streams_file_for_valid_signed_url(): void
    {
        config()->set('backup.backup.destination.disks', ['local']);

        $backupName = config('backup.backup.name');
        $path = $backupName.'/test-backup.zip';
        Storage::disk('local')->put($path, 'backup-content');

        $url = URL::temporarySignedRoute(
            'crm.tools.backups.download',
            now()->addMinutes(5),
            ['disk' => 'local', 'path' => $path]
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertDownload('test-backup.zip');
    }
}
