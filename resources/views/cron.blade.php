@extends('layouts.app')

@section('title', 'Cron | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <div class="row g-5">
            <div class="col-lg-4">
                <h1 class="h2 mb-2">Scrape configurations</h1>
                <p class="text-body-secondary mb-4">
                    Store the keyword and source combinations that will be run automatically later.
                </p>

                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cron.store') }}" class="card shadow-sm">
                    @csrf

                    <div class="card-body">
                        <div class="mb-3">
                            <label for="keywords" class="form-label fw-semibold">Keywords</label>
                            <input
                                type="text"
                                class="form-control"
                                id="keywords"
                                name="keywords"
                                value="{{ old('keywords') }}"
                                placeholder="wordpress developer remote"
                                required
                            >
                        </div>

                        <fieldset class="mb-3">
                            <legend class="form-label fw-semibold mb-2">Sources</legend>
                            @foreach (['linkedin' => 'LinkedIn', 'indeed' => 'Indeed', 'ziprecruiter' => 'ZipRecruiter'] as $value => $label)
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="sources[]"
                                        value="{{ $value }}"
                                        id="source-{{ $value }}"
                                        @checked(in_array($value, old('sources', []), true))
                                    >
                                    <label class="form-check-label" for="source-{{ $value }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </fieldset>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="max-days" class="form-label fw-semibold">Listing age</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="max-days"
                                        name="max_days"
                                        value="{{ old('max_days', 5) }}"
                                        min="1"
                                        max="365"
                                        required
                                    >
                                    <span class="input-group-text">days</span>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label for="interval-hours" class="form-label fw-semibold">Run every</label>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="interval-hours"
                                        name="interval_hours"
                                        value="{{ old('interval_hours', 6) }}"
                                        min="1"
                                        max="8760"
                                        required
                                    >
                                    <span class="input-group-text">hours</span>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="is-active"
                                name="is_active"
                                value="1"
                                @checked((bool) old('is_active', true))
                            >
                            <label class="form-check-label" for="is-active">Enabled</label>
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-primary">Save configuration</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-8">
                <h2 class="h4 mb-3">Saved configurations</h2>

                @if ($schedules->isEmpty())
                    <div class="alert alert-secondary">No scrape configurations have been saved yet.</div>
                @else
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-hover align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Keywords</th>
                                    <th scope="col">Sources</th>
                                    <th scope="col">Lookback</th>
                                    <th scope="col">Frequency</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Last run</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedules as $schedule)
                                    <tr>
                                        <th scope="row">{{ $schedule->keywords }}</th>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($schedule->sources as $source)
                                                    <span class="badge text-bg-primary">{{ ucfirst($source) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>{{ $schedule->max_days }} days</td>
                                        <td>Every {{ $schedule->interval_hours }} hours</td>
                                        <td>
                                            <span class="badge {{ $schedule->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $schedule->is_active ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $schedule->last_ran_at?->timezone(config('app.display_timezone'))->format('M j, Y g:i A') ?? 'Never' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </main>
@endsection
