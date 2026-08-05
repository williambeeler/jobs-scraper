@extends('layouts.app')

@section('title', 'Companies | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Customer relationship
                management</p>
            <h1 class="display-5 fw-bold mb-2">Companies</h1>
            <p class="text-secondary mb-0">
                {{ trans_choice(':count company|:count companies', $companies->total(), ['count' => number_format($companies->total())]) }}
            </p>
        </header>

        @include('crm.companies._index-content')
    </main>
@endsection
