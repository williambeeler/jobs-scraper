@extends('crm::layouts.app')

@section('title', 'Google Platform') @section('heading', 'Google Platform') @section('subtitle',
'Review Google Cloud connection, billing, Maps services, and usage.')

@section('content')@include('crm::partials.tools-tabs')
    <div class="crm-grid">
        <section class="crm-col-4 crm-card">
            <div class="crm-muted">Connection</div>
            <h2>{{ $connectionLabel }}</h2>
            <p>Project: {{ data_get($project, 'id') ?: 'Not configured' }}</p>
            @if ($hasCloudCredentials)
                <form method="POST" action="{{ route('crm.tools.googlePlatform.remove') }}">
                    @csrf<button class="crm-button crm-button-danger"
                        data-confirm="Disconnect Google Cloud?"
                    >Disconnect</button>
                </form>
            @elseif($hasOAuthClientConfig)
                <a class="crm-button crm-button-primary"
                    href="{{ route('crm.tools.googlePlatform.oauth.redirect') }}"
                >Connect Google</a>
            @endif
        </section>
        <section class="crm-col-4 crm-card">
            <div class="crm-muted">Billing</div>
            <h2>{{ data_get($billing, 'billing_enabled') === true ? 'Enabled' : (data_get($billing, 'billing_enabled') === false ? 'Disabled' : 'Unavailable') }}
            </h2>
            <p>{{ data_get($billing, 'message') }}</p>
        </section>
        <section class="crm-col-4 crm-card">
            <div class="crm-muted">Places API</div>
            <h2>{{ $hasPlacesApiKey ? 'Configured' : 'Missing' }}</h2>
        </section>
    </div>
    <section class="crm-card">
        <h2>Enabled Maps Services</h2>
        <p>{{ data_get($enabledMapsServices, 'message') }}</p>
        @foreach (data_get($enabledMapsServices, 'items', []) as $item)
            <span
                class="crm-badge crm-badge-aqua">{{ data_get($item, 'label', data_get($item, 'name')) }}</span>
        @endforeach
    </section>
    <section class="crm-card">
        <h2>Budgets</h2>
        <p>{{ data_get($budgets, 'message') }}</p>
        @foreach (data_get($budgets, 'items', []) as $budget)
            <p>
                <strong>{{ data_get($budget, 'name', data_get($budget, 'display_name')) }}</strong>
                {{ data_get($budget, 'amount') }}
            </p>
        @endforeach
    </section>
@endsection
