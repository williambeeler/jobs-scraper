@extends('layouts.app')

@section('title', 'Applied to | ' . config('app.name'))

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
            @foreach ($listingGroups as $group)
                <section class="mb-5">
                    <h2 class="h5 fw-bold mb-3">
                        {{ $group['label'] }}
                        @if ($group['daysAgo'] !== null)
                            <span
                                class="text-body-secondary fw-normal">({{ trans_choice(':count day ago|:count days ago', $group['daysAgo'], ['count' => $group['daysAgo']]) }})</span>
                        @endif
                    </h2>
                    <div class="list-group shadow-sm">
                        @foreach ($group['listings'] as $listing)
                            <article
                                class="list-group-item p-4 {{ $listing->is_whitelisted ? 'job-row-whitelisted' : ($listing->is_verified ? 'job-row-verified' : '') }}">
                                <div
                                    class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                    <div>
                                        <h3 class="h5 mb-2"><a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer">{{ $listing->title }}</a></h3>
                                        <p class="mb-2 text-body-secondary">
                                            @if ($listing->company_link_url)
                                                <a href="{{ $listing->company_link_url }}" target="_blank" rel="noopener noreferrer">{{ $listing->company->name }}</a>
                                            @else
                                                Unknown company
                                            @endif
                                            @if ($listing->location)
                                                <span aria-hidden="true">&middot;</span>
                                                {{ $listing->location }}
                                            @endif
                                        </p>
                                        <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                            <span>Posted: {{ $listing->posted_age ?? 'Unknown' }}</span>
                                            @if ($listing->salary)
                                                <span>Salary: {{ $listing->salary }}</span>
                                            @endif
                                            <span>{{ 'Applied: ' . $listing->status_changed_at?->timezone($displayTimezone)->format('M j, Y g:i A') }}</span>
                                        </div>
                                    </div>
                                    <div
                                        class="d-flex flex-column align-items-md-end gap-2 flex-shrink-0">
                                        @if ($listing->is_whitelisted)
                                            <span
                                                class="job-row-star"
                                                title="Whitelisted company"
                                                aria-label="Whitelisted company"
                                            >&#9733;</span>
                                        @endif
                                        <span
                                            class="badge text-bg-success align-self-start align-self-md-end"
                                        >{{ $listing->source }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </main>
@endsection
