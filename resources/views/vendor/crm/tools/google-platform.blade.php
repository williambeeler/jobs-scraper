@extends('layouts.app')

@section('title', 'Google Platform | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 1100px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM tools</p>
            <h1 class="display-6 fw-bold mb-2">Google Platform</h1>
            <p class="text-secondary mb-0">Review Google Cloud access, billing, Maps services, and usage.</p>
        </header>

        @include('crm::partials.tools-tabs')

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm"><div class="card-body p-4">
                    <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Connection</p>
                    <h2 class="h5 mb-2">{{ $connectionLabel }}</h2>
                    <p class="text-body-secondary text-break">Project: {{ data_get($project, 'id') ?: 'Not configured' }}</p>
                    @if ($hasCloudCredentials)
                        <form method="POST" action="{{ route('crm.tools.googlePlatform.remove') }}"
                            onsubmit="return confirm('Disconnect Google Cloud?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-danger" type="submit">Disconnect</button>
                        </form>
                    @elseif ($hasOAuthClientConfig)
                        <a class="btn btn-sm btn-primary" href="{{ route('crm.tools.googlePlatform.oauth.redirect') }}">Connect Google</a>
                    @endif
                </div></section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm"><div class="card-body p-4">
                    <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Billing</p>
                    <h2 class="h5 mb-2">{{ data_get($billing, 'billing_enabled') === true ? 'Enabled' : (data_get($billing, 'billing_enabled') === false ? 'Disabled' : 'Unavailable') }}</h2>
                    <p class="text-body-secondary mb-0">{{ data_get($billing, 'message') }}</p>
                </div></section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card h-100 shadow-sm"><div class="card-body p-4">
                    <p class="text-uppercase text-body-secondary fw-semibold small mb-2">Places API</p>
                    <h2 class="h5 mb-2">{{ $hasPlacesApiKey ? 'Configured' : 'Missing' }}</h2>
                    <p class="text-body-secondary mb-0">Google Places enrichment access.</p>
                </div></section>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-6">
                <section class="card h-100 shadow-sm">
                    <div class="card-header bg-white p-4 pb-0 border-0">
                        <h2 class="h5 mb-1">Enabled Maps services</h2>
                        <p class="small text-body-secondary mb-0">{{ data_get($enabledMapsServices, 'message') }}</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-2">
                            @forelse (data_get($enabledMapsServices, 'items', []) as $item)
                                <span class="badge rounded-pill text-bg-info">{{ data_get($item, 'label', data_get($item, 'title', data_get($item, 'name'))) }}</span>
                            @empty
                                <span class="text-body-secondary">No enabled Maps services were returned.</span>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-lg-6">
                <section class="card h-100 shadow-sm">
                    <div class="card-header bg-white p-4 pb-0 border-0">
                        <h2 class="h5 mb-1">Budgets</h2>
                        <p class="small text-body-secondary mb-0">{{ data_get($budgets, 'message') }}</p>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse (data_get($budgets, 'items', []) as $budget)
                            <div class="list-group-item px-4 py-3 d-flex justify-content-between gap-3">
                                <div><h3 class="h6 mb-1">{{ data_get($budget, 'name', data_get($budget, 'display_name')) }}</h3>
                                    @if (data_get($budget, 'thresholds'))<small class="text-body-secondary">Thresholds: {{ data_get($budget, 'thresholds') }}</small>@endif
                                </div>
                                <span class="fw-semibold flex-shrink-0">{{ data_get($budget, 'amount') }}</span>
                            </div>
                        @empty
                            <div class="list-group-item px-4 py-3 text-body-secondary">No budgets were returned.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        <section class="card shadow-sm">
            <div class="card-header bg-white p-4 pb-0 border-0">
                <h2 class="h5 mb-1">Maps activity</h2>
                <p class="small text-body-secondary mb-0">Cloud Monitoring totals for the selected range.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach ([['Requests', data_get($requests, 'total'), data_get($requests, 'message')], ['Errors', data_get($errorMetrics, 'total'), data_get($errorMetrics, 'message')], ['Median latency', data_get($latency, 'median_ms') !== null ? number_format(data_get($latency, 'median_ms'), 2) . ' ms' : null, data_get($latency, 'message')]] as [$label, $value, $message])
                        <div class="col-12 col-md-4">
                            <div class="border rounded p-3 h-100">
                                <p class="small text-body-secondary mb-1">{{ $label }}</p>
                                <p class="h5 mb-2">{{ $value === null ? '—' : (is_numeric($value) ? number_format($value) : $value) }}</p>
                                <p class="small text-body-secondary mb-0">{{ $message }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
