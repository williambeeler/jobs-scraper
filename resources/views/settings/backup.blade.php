@extends('layouts.app')

@section('title', 'Backup | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header
            class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-secondary fw-semibold small">Settings</p>
                <h1 class="display-5 fw-bold mb-2">Database backups</h1>
                <p class="text-secondary mb-0">Create and review SQL database backups stored by the
                    application.</p>
            </div>
            <form method="POST" action="{{ route('settings.backup.store') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Create database backup</button>
            </form>
        </header>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @error('backup')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Previous backups</div>
            <div class="list-group list-group-flush">
                @forelse ($backups as $backup)
                    <div
                        class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <div>
                            <div class="text-break">{{ $backup['path'] }}</div>
                            <div class="text-secondary small">
                                {{ $backup['date']->timezone(config('app.display_timezone'))->format('M j, Y g:i A') }}
                                · {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB ·
                                {{ $backup['disk'] }}</div>
                        </div>
                        <form
                            method="POST"
                            action="{{ route('settings.backup.destroy') }}"
                            onsubmit="return confirm('Delete this backup permanently?');">
                            @csrf
                            @method('DELETE')
                            <input
                                type="hidden"
                                name="disk"
                                value="{{ $backup['disk'] }}">
                            <input
                                type="hidden"
                                name="path"
                                value="{{ $backup['path'] }}">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">No backups have been created yet.</div>
                @endforelse
            </div>
        </div>
    </main>
@endsection
