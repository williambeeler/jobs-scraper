@extends('layouts.app')

@section('title', 'Saved Searches | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM</p>
            <h1 class="display-6 fw-bold mb-2">Saved searches</h1>
            <p class="text-secondary mb-0">Return to frequently used company filters.</p>
        </header>

        @forelse ($savedSearches['data'] as $search)
            @if ($loop->first)
                <div class="list-group shadow-sm">
            @endif
            <article class="list-group-item p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <h2 class="h5 mb-2">{{ $search['name'] }}</h2>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            @forelse ($search['parameters'] as $key => $value)
                                <span
                                    class="badge rounded-pill text-bg-secondary">{{ str($key)->headline() }}:
                                    {{ $value }}</span>
                            @empty
                                <span class="text-body-secondary">No filters saved.</span>
                            @endforelse
                        </div>
                        <small class="text-body-secondary">Saved
                            {{ $search['created_at_label'] }}</small>
                    </div>
                    <div class="align-self-md-center"><a class="btn btn-outline-primary"
                            href="{{ $search['open_url'] }}"
                        >Open search</a></div>
                </div>
            </article>
            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="alert alert-secondary" role="status">No saved searches yet.</div>
        @endforelse
    </main>
@endsection
