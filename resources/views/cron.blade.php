@extends('layouts.app')

@section('title', 'Cron | ' . config('app.name'))

@section('content')
    @php
        $sourceLabels = [
            'linkedin' => 'LinkedIn',
            'dice' => 'Dice',
            'working_nomads' => 'Working Nomads',
            'wordpress_jobs' => 'WordPress Jobs',
            'larajobs' => 'LaraJobs',
            'usajobs' => 'USAJOBS',
        ];
        $selectedSources = old('sources', $editingSchedule?->sources ?? []);
    @endphp

    <main class="container py-5">
        <header class="mb-4">
            <h1 class="h2 mb-2">Scrape configurations</h1>
            <p class="text-body-secondary mb-0">
                Store keyword searches and full-board sweeps that will be run automatically later.
            </p>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
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

        <section aria-labelledby="saved-configurations-heading">
            <h2 class="h4 mb-3" id="saved-configurations-heading">Saved configurations</h2>

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
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schedules as $schedule)
                                <tr
                                    class="{{ $editingSchedule?->is($schedule) ? 'table-primary' : '' }}">
                                    <th scope="row">
                                        {{ $schedule->keywords !== '' ? $schedule->keywords : 'All listings' }}
                                    </th>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($schedule->sources as $source)
                                                <span class="badge text-bg-primary">
                                                    {{ $sourceLabels[$source] ?? ucfirst($source) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>{{ $schedule->max_days }} days</td>
                                    <td>Every {{ $schedule->interval_hours }} hours</td>
                                    <td>
                                        <span
                                            class="badge {{ $schedule->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                            {{ $schedule->is_active ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $schedule->last_ran_at?->timezone(config('app.display_timezone'))->format('M j, Y g:i A') ?? 'Never' }}
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST"
                                                action="{{ route('jobs.cron.schedule.run', $schedule) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm btn-success">Run now</button>
                                            </form>
                                            <a href="{{ route('jobs.cron.edit', $schedule) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >Edit</a>
                                            <form
                                                method="POST"
                                                action="{{ route('jobs.cron.destroy', $schedule) }}"
                                                onsubmit="return confirm('Delete this scrape configuration? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="mt-5" aria-labelledby="configuration-form-heading">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0" id="configuration-form-heading">
                    {{ $editingSchedule ? 'Edit configuration' : 'Add configuration' }}
                </h2>
                @if ($editingSchedule)
                    <a href="{{ route('jobs.cron') }}" class="btn btn-sm btn-outline-secondary">Cancel
                        editing</a>
                @endif
            </div>

            <form
                method="POST"
                action="{{ $editingSchedule ? route('jobs.cron.update', $editingSchedule) : route('jobs.cron.store') }}"
                class="card shadow-sm">
                @csrf
                @if ($editingSchedule)
                    @method('PUT')
                @endif

                <div class="card-body">
                    <div class="mb-4">
                        <label for="keywords" class="form-label fw-semibold">Keywords</label>
                        <input
                            type="text"
                            class="form-control"
                            id="keywords"
                            name="keywords"
                            value="{{ old('keywords', $editingSchedule?->keywords ?? '') }}"
                            placeholder="wordpress developer remote">
                        <div class="form-text">Required for LinkedIn, Dice, Working Nomads, and USAJOBS; ignored
                            by full-board sources.</div>
                    </div>

                    <fieldset class="mb-4">
                        <legend class="form-label fw-semibold mb-2">Sources</legend>
                        <div class="row g-2">
                            @foreach ([
            'linkedin' => 'LinkedIn',
            'dice' => 'Dice',
            'working_nomads' => 'Working Nomads',
            'wordpress_jobs' => 'WordPress Jobs (all listings)',
            'larajobs' => 'LaraJobs (all listings)',
            'usajobs' => 'USAJOBS (remote only)',
        ] as $value => $label)
                                <div class="col-sm-6 col-lg-4">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="sources[]"
                                            value="{{ $value }}"
                                            id="source-{{ $value }}"
                                            @checked(in_array($value, $selectedSources, true))>
                                        <label class="form-check-label"
                                            for="source-{{ $value }}"
                                        >{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-lg-3">
                            <label for="max-days" class="form-label fw-semibold">Listing age</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    id="max-days"
                                    name="max_days"
                                    value="{{ old('max_days', $editingSchedule?->max_days ?? 5) }}"
                                    min="1"
                                    max="365"
                                    required>
                                <span class="input-group-text">days</span>
                            </div>
                        </div>

                        <div class="col-sm-6 col-lg-3">
                            <label for="interval-hours" class="form-label fw-semibold">Run every</label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    id="interval-hours"
                                    name="interval_hours"
                                    value="{{ old('interval_hours', $editingSchedule?->interval_hours ?? 6) }}"
                                    min="1"
                                    max="8760"
                                    required>
                                <span class="input-group-text">hours</span>
                            </div>
                        </div>
                    </div>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            id="is-active"
                            name="is_active"
                            value="1"
                            @checked((bool) old('is_active', $editingSchedule?->is_active ?? true))>
                        <label class="form-check-label" for="is-active">Enabled</label>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $editingSchedule ? 'Update configuration' : 'Save configuration' }}
                    </button>
                    @if ($editingSchedule)
                        <a href="{{ route('jobs.cron') }}" class="btn btn-outline-secondary">Cancel</a>
                    @endif
                </div>
            </form>
        </section>
    </main>
@endsection
