@extends('layouts.app')

@section('title', $company->name . ' jobs | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Company job history</p>
            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                <h1 class="display-5 fw-bold mb-0">{{ $company->name }}</h1>
                @if ($company->preference)
                    <span class="badge {{ match ($company->preference->type->value) {
                        'whitelist' => 'text-bg-warning',
                        'verified' => 'text-bg-info',
                        'blacklist' => 'text-bg-dark',
                        default => 'text-bg-secondary',
                    } }}">
                        {{ ucfirst($company->preference->type->value) }}
                    </span>
                @endif
            </div>
            <p class="text-secondary mb-0">
                Every job associated with this company, including ignored, applied, hidden, and custom jobs.
            </p>
        </header>

        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <p class="mb-0 text-body-secondary">
                {{ trans_choice(':count job|:count jobs', $listings->total(), ['count' => $listings->total()]) }}
            </p>
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('jobs.company-preferences.index') }}">
                Back to companies
            </a>
        </div>

        @if ($listings->isEmpty())
            <div class="alert alert-secondary">No jobs are currently associated with this company.</div>
        @else
            <div class="list-group shadow-sm">
                @foreach ($listings as $listing)
                    <article class="list-group-item p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <h2 class="h5 mb-2">
                                    <a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer">
                                        {{ $listing->title }}
                                    </a>
                                </h2>
                                <p class="mb-3 text-body-secondary">
                                    {{ $listing->location ?: 'Location not provided' }}
                                </p>
                                <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                    <span>Posted: {{ $listing->posted_at?->format('M j, Y') ?? 'Unknown' }}</span>
                                    <span>First collected: {{ $listing->created_at?->format('M j, Y') ?? 'Unknown' }}</span>
                                    <span>Last seen: {{ $listing->last_seen_at?->format('M j, Y') ?? 'Unknown' }}</span>
                                    @if ($listing->salary)
                                        <span>Salary: {{ $listing->salary }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-wrap flex-md-column align-items-start align-items-md-end gap-2 flex-shrink-0">
                                <span class="badge text-bg-primary">{{ $listing->source }}</span>
                                <span class="badge text-bg-light border">
                                    {{ $listing->listing_origin->value === 'custom' ? 'Custom job' : 'Scraped job' }}
                                </span>
                                <span class="badge {{ match ($listing->status?->value) {
                                    'applied' => 'text-bg-success',
                                    'not_interested' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                } }}">
                                    {{ match ($listing->status?->value) {
                                        'applied' => 'Applied',
                                        'not_interested' => 'Not interested',
                                        default => 'No decision',
                                    } }}
                                </span>
                                <span class="badge text-bg-light border">
                                    Kanban: {{ str($listing->kanban_status->value)->replace('_', ' ')->title() }}
                                </span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($listings->hasPages())
                <div class="mt-4">{{ $listings->links() }}</div>
            @endif
        @endif
    </main>
@endsection
