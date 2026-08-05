@extends('layouts.app')

@section('title', 'OpenAI Platform | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 1100px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM tools</p>
            <h1 class="display-6 fw-bold mb-2">OpenAI</h1>
            <p class="text-secondary mb-0">Review API access, account identity, costs, and usage.</p>
        </header>

        @include('crm.partials.tools-tabs')

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Connection
                        </p>
                        <h2 class="h5 mb-2">{{ $keyModeLabel }}</h2>
                        <p class="text-body-secondary mb-0">
                            {{ $hasApiKey ? 'Standard API key configured.' : 'Standard API key is missing.' }}
                        </p>
                    </div>
                </section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Identity
                        </p>
                        <h2 class="h5 mb-2">{{ data_get($identity, 'name') ?: 'Unavailable' }}</h2>
                        <p class="text-body-secondary text-break mb-0">
                            {{ data_get($identity, 'email') ?: 'No identity returned.' }}</p>
                    </div>
                </section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Current
                            costs</p>
                        <h2 class="h5 mb-2">
                            {{ data_get($costs, 'total') !== null ? data_get($costs, 'currency', '$') . number_format(data_get($costs, 'total'), 2) : 'Unavailable' }}
                        </h2>
                        <p class="text-body-secondary mb-0">{{ data_get($costs, 'range_label') }}</p>
                    </div>
                </section>
            </div>
        </div>

        <section class="card shadow-sm mb-4">
            <div class="card-header bg-white p-4 pb-0 border-0">
                <h2 class="h5 mb-1">Usage key</h2>
                <p class="small text-body-secondary mb-0">Connect a key with organization usage
                    permissions.</p>
            </div>
            <div class="card-body p-4">
                @if ($hasUsageApiKey)
                    <div
                        class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <p class="mb-0 text-success fw-semibold">Usage API key connected.</p>
                        <form
                            method="POST"
                            action="{{ route('crm.tools.openAiPlatform.usageKey.remove') }}"
                            onsubmit="return confirm('Remove the OpenAI usage key?')"
                        >
                            @csrf
                            <button class="btn btn-outline-danger" type="submit">Remove usage
                                key</button>
                        </form>
                    </div>
                @else
                    <form method="POST"
                        action="{{ route('crm.tools.openAiPlatform.usageKey.store') }}">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md">
                                <label class="form-label fw-semibold" for="usage_api_key">Usage API
                                    key</label>
                                <input
                                    class="form-control @error('usage_api_key') is-invalid @enderror"
                                    id="usage_api_key"
                                    type="password"
                                    name="usage_api_key"
                                    required
                                    autocomplete="off"
                                >
                                @error('usage_api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-auto"><button class="btn btn-primary"
                                    type="submit"
                                >Connect key</button></div>
                        </div>
                    </form>
                @endif
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-header bg-white p-4 pb-3">
                <h2 class="h5 mb-1">Usage</h2>
                <p class="small text-body-secondary mb-0">{{ data_get($usage, 'message') }}</p>
            </div>
            <div class="card-body p-4">
                @if (data_get($costs, 'message'))
                    <div class="alert {{ data_get($costs, 'status') === 'available' ? 'alert-light' : 'alert-warning' }}"
                        role="status"
                    >
                        {{ data_get($costs, 'message') }}
                    </div>
                @endif
                <div class="row g-3 mb-4">
                    @foreach ([['Requests', data_get($usage, 'requests')], ['Input tokens', data_get($usage, 'input_tokens')], ['Output tokens', data_get($usage, 'output_tokens')]] as [$label, $value])
                        <div class="col-12 col-sm-4">
                            <div class="border rounded p-3 h-100">
                                <p class="small text-body-secondary mb-1">{{ $label }}</p>
                                <p class="h5 mb-0">{{ $value === null ? '—' : number_format($value) }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if (data_get($usage, 'daily_requests', []))
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="text-end">Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (data_get($usage, 'daily_requests', []) as $day)
                                    <tr>
                                        <td>{{ data_get($day, 'label') }}</td>
                                        <td class="text-end">
                                            {{ number_format(data_get($day, 'value', data_get($day, 'requests', 0))) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-body-secondary mb-0">No daily usage data is available.</p>
                @endif
            </div>
        </section>
    </main>
@endsection
