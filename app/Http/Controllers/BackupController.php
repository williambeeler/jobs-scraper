<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Throwable;

class BackupController extends Controller
{
    public function index(): View
    {
        $backups = BackupDestinationFactory::createFromArray(app(Config::class))
            ->flatMap(fn (BackupDestination $destination) => $destination->backups()
                ->map(fn (Backup $backup): array => [
                    'disk' => $destination->diskName(),
                    'path' => $backup->path(),
                    'date' => $backup->date(),
                    'size' => $backup->sizeInBytes(),
                ]))
            ->sortByDesc('date')
            ->values();

        return view('settings.backup', compact('backups'));
    }

    public function store(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => 'The database backup could not be created. Check the application log for details.']);
        }

        if ($exitCode !== 0) {
            return back()->withErrors(['backup' => 'The database backup could not be created. '.trim(Artisan::output())]);
        }

        return to_route('settings.backup')->with('status', 'Database backup created successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
        ]);

        $destination = BackupDestinationFactory::createFromArray(app(Config::class))
            ->first(fn (BackupDestination $destination) => $destination->diskName() === $validated['disk']);
        $backup = $destination?->backups()->first(
            fn (Backup $backup) => hash_equals($backup->path(), $validated['path']),
        );

        if ($backup === null) {
            return back()->withErrors(['backup' => 'That backup could not be found. Refresh the list and try again.']);
        }

        try {
            $backup->delete();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['backup' => 'The backup could not be deleted. Check the application log for details.']);
        }

        return to_route('settings.backup')->with('status', 'Backup deleted.');
    }
}
