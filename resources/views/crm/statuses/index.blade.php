@extends('layouts.app')

@section('title', 'Statuses | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 1100px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM tools</p>
            <h1 class="display-6 fw-bold mb-2">Statuses and relationships</h1>
            <p class="text-secondary mb-0">Review the classifications available to CRM companies.</p>
        </header>

        @include('crm.partials.tools-tabs')

        <form class="card card-body shadow-sm mb-4" method="GET">
            <label class="form-label fw-semibold" for="search_term">Search classifications</label>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <input
                    class="form-control"
                    id="search_term"
                    name="search_term"
                    value="{{ $filters['search_term'] }}"
                    placeholder="Search statuses and relationships"
                >
                <button class="btn btn-primary flex-shrink-0" type="submit">Search</button>
                @if ($filters['search_term'])
                    <a class="btn btn-outline-secondary flex-shrink-0"
                        href="{{ route('crm.statuses.index') }}"
                    >Clear</a>
                @endif
            </div>
        </form>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <section class="card h-100 shadow-sm">
                    <div class="card-header bg-white p-4 pb-3">
                        <h2 class="h5 mb-1">Statuses</h2>
                        <p class="small text-body-secondary mb-0">{{ $statuses['meta']['total'] }}
                            available</p>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($statuses['data'] as $status)
                            <a class="list-group-item list-group-item-action p-4"
                                href="{{ $status['show_url'] }}"
                            >
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h3 class="h6 mb-1">{{ $status['name'] }}</h3>
                                        <p class="small text-body-secondary mb-0">
                                            {{ $status['description'] ?: 'No description.' }}</p>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <code>{{ $status['slug'] }}</code>
                                        @if ($status['is_default'])
                                            <span
                                                class="badge text-bg-primary d-block mt-2">Default</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="list-group-item p-4 text-body-secondary">No statuses match this
                                search.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-6">
                <section class="card h-100 shadow-sm">
                    <div class="card-header bg-white p-4 pb-3">
                        <h2 class="h5 mb-1">Relationships</h2>
                        <p class="small text-body-secondary mb-0">{{ $relationships['meta']['total'] }}
                            available</p>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($relationships['data'] as $relationship)
                            <div class="list-group-item p-4">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h3 class="h6 mb-1">{{ $relationship['name'] }}</h3>
                                        <p class="small text-body-secondary mb-0">
                                            {{ $relationship['description'] ?: 'No description.' }}</p>
                                    </div>
                                    <code class="flex-shrink-0">{{ $relationship['slug'] }}</code>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item p-4 text-body-secondary">No relationships match
                                this search.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>
@endsection
