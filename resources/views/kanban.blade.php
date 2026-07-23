@extends('layouts.app')

@section('title', 'Jobs Kanban | '.config('app.name'))

@section('content')
    <main class="container-fluid px-4 py-5">
        <header class="d-flex align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-secondary fw-semibold small">Tools</p>
                <h1 class="display-5 fw-bold mb-0">Jobs Kanban</h1>
            </div>
            <button type="button" class="btn btn-primary flex-shrink-0">+ Add Job</button>
        </header>

        <div class="kanban-board pb-3" aria-label="Jobs Kanban board">
            @foreach ($columns as $column)
                @php($columnListings = $listingsByStatus->get($column['status']->value, collect()))
                <section class="kanban-column" aria-labelledby="kanban-{{ $column['status']->value }}-title">
                    <header class="kanban-column-header">
                        <h2 class="h6 fw-bold mb-0" id="kanban-{{ $column['status']->value }}-title">
                            {{ $column['title'] }}
                        </h2>
                        <span class="badge rounded-pill text-bg-secondary" data-column-count>{{ $columnListings->count() }}</span>
                    </header>

                    <div
                        class="kanban-column-body"
                        data-kanban-column
                        data-column-id="{{ $column['status']->value }}"
                    >
                        @foreach ($columnListings as $listing)
                            <article
                                class="kanban-card {{ $listing->is_whitelisted ? 'kanban-card-whitelisted' : '' }}"
                                data-job-card
                                data-update-url="{{ route('jobs.kanban-status', $listing) }}"
                                data-status-url="{{ route('jobs.status', $listing) }}"
                            >
                                <h3 class="h6 fw-bold mb-2">
                                    <a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer" data-no-drag>{{ $listing->title }}</a>
                                </h3>
                                <p class="small text-body-secondary mb-2">
                                    {{ $listing->company ?: 'Unknown company' }}
                                    @if ($listing->location)
                                        <span aria-hidden="true">&middot;</span> {{ $listing->location }}
                                    @endif
                                </p>
                                <div class="d-flex flex-wrap gap-2 align-items-center small text-body-secondary">
                                    <span class="badge text-bg-light border">{{ $listing->source }}</span>
                                    <span>{{ $listing->posted_relative ?: ($listing->posted_at?->format('M j, Y') ?? 'Posted date unknown') }}</span>
                                    @if ($listing->salary)
                                        <span>{{ $listing->salary }}</span>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger w-100 mt-3 {{ in_array($column['status']->value, ['backlog', 'interested'], true) ? '' : 'd-none' }}"
                                    data-not-interested
                                    data-no-drag
                                >
                                    Not Interested
                                </button>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </main>
@endsection

@push('styles')
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            align-items: stretch;
        }

        .kanban-column {
            display: flex;
            flex: 0 0 18rem;
            flex-direction: column;
            min-height: 65vh;
            overflow: hidden;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            background: #f1f3f5;
        }

        .kanban-column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            background: #fff;
        }

        .kanban-column-body {
            flex: 1;
            min-height: 10rem;
            padding: .75rem;
        }

        .kanban-card {
            padding: 1rem;
            margin-bottom: .75rem;
            border: 1px solid #dee2e6;
            border-radius: .625rem;
            background: #fff;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            cursor: grab;
        }

        .kanban-card:active {
            cursor: grabbing;
        }

        .kanban-card-whitelisted {
            border-left: 4px solid #d5a72e;
            background: linear-gradient(to bottom, #fff 15%, #fff8df 100%);
        }

        .kanban-card-ghost {
            opacity: .35;
        }

        .kanban-card-chosen {
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
        }
    </style>
@endpush

@push('scripts')
    @vite('resources/js/kanban.ts')
@endpush
