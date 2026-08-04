@extends('layouts.app')

@section('title', 'CRM Tools | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 1100px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM</p>
            <h1 class="display-6 fw-bold mb-2">Tools</h1>
            <p class="text-secondary mb-0">Manage CRM statuses and platform connections.</p>
        </header>

        @include('crm::partials.tools-tabs')
    </main>
@endsection
