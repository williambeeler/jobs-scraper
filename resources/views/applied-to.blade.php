@extends('layouts.app')

@section('title', 'Applied to | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Job application history</p>
            <h1 class="display-5 fw-bold mb-2">Applied to</h1>
            <p class="text-secondary mb-0">
                {{ trans_choice(':count application|:count applications', $listings->count(), ['count' => $listings->count()]) }}
            </p>
        </header>

        @if ($listings->isEmpty())
            <div class="alert alert-secondary" role="status">
                You have not marked any jobs as applied yet.
            </div>
        @else
            <div class="list-group shadow-sm">
                @foreach ($listings as $listing)
                    <a
                        href="{{ $listing->url }}"
                        class="list-group-item list-group-item-action p-4 {{ $listing->is_whitelisted ? 'job-row-whitelisted' : '' }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div>
                                <h2 class="h5 mb-2">{{ $listing->title }}</h2>
                                <p class="mb-2 text-body-secondary">
                                    {{ $listing->company ?: 'Unknown company' }}
                                    @if ($listing->location)
                                        <span aria-hidden="true">&middot;</span> {{ $listing->location }}
                                    @endif
                                </p>
                                <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                    <span>Posted: {{ $listing->posted_relative ?: ($listing->posted_at?->format('M j, Y') ?? 'Unknown') }}</span>
                                    @if ($listing->salary)
                                        <span>Salary: {{ $listing->salary }}</span>
                                    @endif
                                    <span>Applied: {{ $listing->status_changed_at?->timezone($displayTimezone)->format('M j, Y g:i A') }}</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-md-end gap-2 flex-shrink-0">
                                @if ($listing->is_whitelisted)
                                    <span class="job-row-star" title="Whitelisted company" aria-label="Whitelisted company">&#9733;</span>
                                @endif
                                <span class="badge text-bg-success align-self-start align-self-md-end">{{ $listing->source }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
@endsection
