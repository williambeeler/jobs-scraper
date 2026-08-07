@extends('layouts.app')

@section('title', 'Jobs Kanban | ' . config('app.name'))

@section('content')
    <main class="container-fluid px-4 py-5">
        <header class="d-flex align-items-end justify-content-between gap-3 mb-4">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <p class="mb-2 text-uppercase text-secondary fw-semibold small">Tools</p>
                    <h1 class="display-5 fw-bold mb-0">Jobs Kanban</h1>
                </div>
                <div class="kanban-search">
                    <label class="visually-hidden" for="kanban-job-search">Search Kanban jobs</label>
                    <input
                        type="search"
                        class="form-control"
                        id="kanban-job-search"
                        placeholder="Search jobs or companies..."
                        autocomplete="off"
                        data-kanban-search
                        aria-controls="kanban-board">
                </div>
            </div>
            <button
                type="button"
                class="btn btn-primary flex-shrink-0"
                data-bs-toggle="modal"
                data-bs-target="#add-job-modal"
            >+ Add Job</button>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="kanban-board pb-3" id="kanban-board" aria-label="Jobs Kanban board">
            @foreach ($columns as $column)
                @php($columnListings = $listingsByStatus->get($column['status']->value, collect()))
                <section class="kanban-column"
                    aria-labelledby="kanban-{{ $column['status']->value }}-title">
                    <header class="kanban-column-header">
                        <h2 class="h6 fw-bold mb-0" id="kanban-{{ $column['status']->value }}-title">
                            {{ $column['title'] }}
                        </h2>
                        <div class="d-flex align-items-center gap-2">
                            @if ($column['status'] === \App\Enums\KanbanStatus::Rejected)
                                <form
                                    method="POST"
                                    action="{{ route('jobs.kanban.rejected.clear') }}"
                                    onsubmit="return confirm('Clear all rejected jobs from the Kanban?');">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-secondary py-0"
                                        {{ $columnListings->isEmpty() ? 'disabled' : '' }}>Clear</button>
                                </form>
                            @endif
                            <span class="badge rounded-pill text-bg-secondary"
                                data-column-count>{{ $columnListings->count() }}</span>
                        </div>
                    </header>

                    <div
                        class="kanban-column-body"
                        data-kanban-column
                        data-column-id="{{ $column['status']->value }}">
                        @foreach ($columnListings as $listing)
                            <article
                                class="kanban-card {{ $listing->is_whitelisted ? 'kanban-card-whitelisted' : ($listing->is_verified ? 'kanban-card-verified' : '') }}"
                                data-job-card
                                data-update-url="{{ route('jobs.kanban-status', $listing) }}"
                                data-status-url="{{ route('jobs.status', $listing) }}"
                                data-listing-origin="{{ $listing->listing_origin->value }}">
                                <h3 class="h6 fw-bold mb-2">
                                    <a
                                        href="{{ $listing->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-no-drag
                                    >{{ $listing->title }}</a>
                                </h3>
                                <p class="small text-body-secondary mb-2">
                                    @if ($listing->company_link_url)
                                        <a href="{{ $listing->company_link_url }}" target="_blank" rel="noopener noreferrer" data-no-drag>{{ $listing->company->name }}</a>
                                    @else
                                        Unknown company
                                    @endif
                                    @if ($listing->location)
                                        <span aria-hidden="true">&middot;</span>
                                        {{ $listing->location }}
                                    @endif
                                </p>
                                <div
                                    class="d-flex flex-wrap gap-2 align-items-center small text-body-secondary">
                                    <span
                                        class="badge text-bg-light border">{{ $listing->source }}</span>
                                    <span>{{ $listing->posted_age ?? 'Posted date unknown' }}</span>
                                    @if ($listing->salary)
                                        <span>{{ $listing->salary }}</span>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary w-100 mt-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#job-details-{{ $listing->id }}"
                                    data-no-drag>
                                    Details &amp; Edit
                                </button>
                                @if ($listing->listing_origin->value === 'scraped')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger w-100 mt-3 {{ in_array($column['status']->value, ['backlog', 'interested'], true) ? '' : 'd-none' }}"
                                        data-not-interested
                                        data-no-drag>
                                        Not Interested
                                    </button>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </main>

    @foreach ($listingsByStatus->flatten(1) as $listing)
        <div
            class="modal fade"
            id="job-details-{{ $listing->id }}"
            tabindex="-1"
            aria-labelledby="job-details-{{ $listing->id }}-title"
            aria-hidden="true"
            data-open-on-load="{{ $errors->listingDetails->any() && (int) old('listing_id') === $listing->id ? 'true' : 'false' }}">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('jobs.details.update', $listing) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="listing_id" value="{{ $listing->id }}">

                        <div class="modal-header">
                            <div>
                                <p class="small text-uppercase text-secondary fw-semibold mb-1">Job details</p>
                                <h2 class="modal-title fs-5" id="job-details-{{ $listing->id }}-title">
                                    {{ $listing->title }}
                                </h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="job-{{ $listing->id }}-title">Job title</label>
                                    <input
                                        class="form-control @if ((int) old('listing_id') === $listing->id) @error('title', 'listingDetails') is-invalid @enderror @endif"
                                        id="job-{{ $listing->id }}-title"
                                        name="title"
                                        value="{{ (int) old('listing_id') === $listing->id ? old('title', $listing->title) : $listing->title }}"
                                        required>
                                    @if ((int) old('listing_id') === $listing->id)
                                        @error('title', 'listingDetails') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="job-{{ $listing->id }}-company">Company</label>
                                    <input class="form-control" id="job-{{ $listing->id }}-company" name="company" value="{{ (int) old('listing_id') === $listing->id ? old('company', $listing->company?->name) : $listing->company?->name }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="job-{{ $listing->id }}-location">Location</label>
                                    <input class="form-control" id="job-{{ $listing->id }}-location" name="location" value="{{ (int) old('listing_id') === $listing->id ? old('location', $listing->location) : $listing->location }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="job-{{ $listing->id }}-url">Job URL</label>
                                    <input
                                        type="url"
                                        class="form-control @if ((int) old('listing_id') === $listing->id) @error('url_hash', 'listingDetails') is-invalid @enderror @endif"
                                        id="job-{{ $listing->id }}-url"
                                        name="url"
                                        value="{{ (int) old('listing_id') === $listing->id ? old('url', $listing->url) : $listing->url }}"
                                        required>
                                    @if ((int) old('listing_id') === $listing->id)
                                        @error('url_hash', 'listingDetails') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="job-{{ $listing->id }}-source">Source</label>
                                    <input class="form-control" id="job-{{ $listing->id }}-source" name="source" maxlength="32" value="{{ (int) old('listing_id') === $listing->id ? old('source', $listing->source) : $listing->source }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="job-{{ $listing->id }}-posted-at">Posted date</label>
                                    <input type="date" class="form-control" id="job-{{ $listing->id }}-posted-at" name="posted_at" value="{{ (int) old('listing_id') === $listing->id ? old('posted_at', $listing->posted_at?->toDateString()) : $listing->posted_at?->toDateString() }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="job-{{ $listing->id }}-salary">Salary</label>
                                    <input class="form-control" id="job-{{ $listing->id }}-salary" name="salary" value="{{ (int) old('listing_id') === $listing->id ? old('salary', $listing->salary) : $listing->salary }}">
                                </div>
                            </div>

                            <hr class="my-4">
                            <dl class="row small mb-0">
                                <dt class="col-sm-4">Listing origin</dt>
                                <dd class="col-sm-8">{{ ucfirst($listing->listing_origin->value) }}</dd>
                                <dt class="col-sm-4">Decision status</dt>
                                <dd class="col-sm-8">{{ $listing->status?->value ? str($listing->status->value)->replace('_', ' ')->title() : 'No decision' }}</dd>
                                <dt class="col-sm-4">Kanban stage</dt>
                                <dd class="col-sm-8">{{ str($listing->kanban_status->value)->replace('_', ' ')->title() }}</dd>
                                <dt class="col-sm-4">First collected</dt>
                                <dd class="col-sm-8">{{ $listing->first_seen_at?->format('M j, Y g:i A') }}</dd>
                                <dt class="col-sm-4">Last seen</dt>
                                <dd class="col-sm-8">{{ $listing->last_seen_at?->format('M j, Y g:i A') }}</dd>
                            </dl>
                        </div>

                        <div class="modal-footer">
                            <a class="btn btn-outline-secondary me-auto" href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer">Open job posting</a>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div
        class="modal fade"
        id="add-job-modal"
        tabindex="-1"
        aria-labelledby="add-job-modal-title"
        aria-hidden="true"
        data-open-on-load="{{ $errors->customJob->any() ? 'true' : 'false' }}">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('jobs.custom.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="add-job-modal-title">Add Custom Job</h2>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="custom-job-title" class="form-label">Job title</label>
                                <input
                                    type="text"
                                    class="form-control @error('title', 'customJob') is-invalid @enderror"
                                    id="custom-job-title"
                                    name="title"
                                    value="{{ old('title') }}"
                                    required>
                                @error('title', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="custom-job-company" class="form-label">Company</label>
                                <input
                                    type="text"
                                    class="form-control @error('company', 'customJob') is-invalid @enderror"
                                    id="custom-job-company"
                                    name="company"
                                    value="{{ old('company') }}">
                                @error('company', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="custom-job-location" class="form-label">Location</label>
                                <input
                                    type="text"
                                    class="form-control @error('location', 'customJob') is-invalid @enderror"
                                    id="custom-job-location"
                                    name="location"
                                    value="{{ old('location') }}">
                                @error('location', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="custom-job-url" class="form-label">Job URL</label>
                                <input
                                    type="url"
                                    class="form-control @error('url', 'customJob') is-invalid @enderror"
                                    id="custom-job-url"
                                    name="url"
                                    value="{{ old('url') }}"
                                    required>
                                @error('url', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="custom-job-source" class="form-label">Source</label>
                                <input
                                    type="text"
                                    class="form-control @error('source', 'customJob') is-invalid @enderror"
                                    id="custom-job-source"
                                    name="source"
                                    value="{{ old('source', 'Custom') }}"
                                    required>
                                @error('source', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="custom-job-posted-at" class="form-label">Posted
                                    date</label>
                                <input
                                    type="date"
                                    class="form-control @error('posted_at', 'customJob') is-invalid @enderror"
                                    id="custom-job-posted-at"
                                    name="posted_at"
                                    value="{{ old('posted_at') }}">
                                @error('posted_at', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="custom-job-salary" class="form-label">Salary</label>
                                <input
                                    type="text"
                                    class="form-control @error('salary', 'customJob') is-invalid @enderror"
                                    id="custom-job-salary"
                                    name="salary"
                                    value="{{ old('salary') }}">
                                @error('salary', 'customJob')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                        >Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            align-items: stretch;
        }

        .kanban-search {
            width: min(22rem, 75vw);
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

        .kanban-card-verified {
            border-left: 4px solid #63aee8;
            background: linear-gradient(to bottom, #fff 15%, #e8f5ff 100%);
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
