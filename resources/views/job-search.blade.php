@extends('layouts.app')

@section('title', 'Job Search | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Complete job archive</p>
            <h1 class="display-5 fw-bold mb-2">Job Search</h1>
            <p class="text-secondary mb-0">Search every listing, including jobs from blacklisted companies and jobs previously hidden from the daily view.</p>
        </header>

        <form method="GET" action="{{ route('job-search') }}" class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="job-search-query" class="form-label fw-semibold">Search terms</label>
                        <input type="search" class="form-control form-control-lg" id="job-search-query" name="q" value="{{ $filters['q'] }}" placeholder="Job title, company, location, source, or salary">
                        <div class="form-text">Every word must match somewhere in the listing, but the words can match different fields.</div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="company-name" class="form-label">Company name contains</label>
                        <input type="text" class="form-control" id="company-name" name="company" value="{{ $filters['company'] }}" placeholder="CACI, Automattic, Acme">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="company-type" class="form-label">Company type</label>
                        <select class="form-select" id="company-type" name="company_type">
                            <option value="any" @selected($filters['company_type'] === 'any')>All companies</option>
                            <option value="whitelist" @selected($filters['company_type'] === 'whitelist')>Whitelisted</option>
                            <option value="verified" @selected($filters['company_type'] === 'verified')>Verified</option>
                            <option value="blacklist" @selected($filters['company_type'] === 'blacklist')>Blacklisted</option>
                            <option value="none" @selected($filters['company_type'] === 'none')>No preference</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <label for="job-source" class="form-label">Source</label>
                        <select class="form-select" id="job-source" name="source">
                            <option value="">All sources</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <label for="job-status" class="form-label">Job status</label>
                        <select class="form-select" id="job-status" name="status">
                            <option value="any" @selected($filters['status'] === 'any')>All statuses</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="applied" @selected($filters['status'] === 'applied')>Applied</option>
                            <option value="not_interested" @selected($filters['status'] === 'not_interested')>Not interested</option>
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3">
                        <label for="job-origin" class="form-label">Listing origin</label>
                        <select class="form-select" id="job-origin" name="origin">
                            <option value="any" @selected($filters['origin'] === 'any')>All origins</option>
                            <option value="scraped" @selected($filters['origin'] === 'scraped')>Scraped</option>
                            <option value="custom" @selected($filters['origin'] === 'custom')>Custom</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="job-location" class="form-label">Location contains</label>
                        <input type="text" class="form-control" id="job-location" name="location" value="{{ $filters['location'] }}" placeholder="Remote, Chicago, USA">
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="salary-availability" class="form-label">Salary availability</label>
                        <select class="form-select" id="salary-availability" name="salary">
                            <option value="any" @selected($filters['salary'] === 'any')>Any</option>
                            <option value="provided" @selected($filters['salary'] === 'provided')>Salary provided</option>
                            <option value="missing" @selected($filters['salary'] === 'missing')>No salary provided</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label for="salary-text" class="form-label">Salary contains</label>
                        <input type="text" class="form-control" id="salary-text" name="salary_text" value="{{ $filters['salary_text'] }}" placeholder="120,000 or hourly">
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label for="posted-from" class="form-label">Posted from</label>
                        <input type="date" class="form-control" id="posted-from" name="posted_from" value="{{ $filters['posted_from'] }}">
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label for="posted-to" class="form-label">Posted through</label>
                        <input type="date" class="form-control" id="posted-to" name="posted_to" value="{{ $filters['posted_to'] }}">
                    </div>

                    <div class="col-md-4 col-lg-2">
                        <label for="results-per-page" class="form-label">Results per page</label>
                        <select class="form-select" id="results-per-page" name="per_page">
                            @foreach ([25, 50, 100] as $amount)
                                <option value="{{ $amount }}" @selected($filters['per_page'] === $amount)>{{ $amount }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Search jobs</button>
                <a href="{{ route('job-search') }}" class="btn btn-outline-secondary">Clear filters</a>
            </div>
        </form>

        @if (! $hasSearched)
            <div class="alert alert-primary py-4 text-center">
                <h2 class="h4 mb-1">Ready to search through {{ number_format($totalJobs) }} jobs</h2>
                <p class="mb-0">Choose any combination of filters above, then select Search jobs.</p>
            </div>
        @else
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h4 mb-0">{{ number_format($listings->total()) }} matching jobs</h2>
                @if ($listings->total())
                    <span class="small text-secondary">Showing {{ number_format($listings->firstItem()) }}–{{ number_format($listings->lastItem()) }}</span>
                @endif
            </div>
        @endif

        @if ($hasSearched)
        @forelse ($listings as $listing)
            @php($preference = $listing->company?->preference?->type)
            <article class="card shadow-sm mb-3 {{ $preference?->value === 'blacklist' ? 'border-danger-subtle' : '' }}">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                        <div>
                            <h3 class="h5 mb-1"><a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer">{{ $listing->title }}</a></h3>
                            <p class="text-secondary mb-2">
                                {{ $listing->company?->name ?? 'Unknown company' }}
                                @if ($listing->location)<span aria-hidden="true">&middot;</span> {{ $listing->location }}@endif
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-primary">{{ $listing->source }}</span>
                                @if ($preference)
                                    <span class="badge {{ match ($preference->value) { 'whitelist' => 'text-bg-warning', 'verified' => 'text-bg-info', 'blacklist' => 'text-bg-danger' } }}">{{ ucfirst($preference->value) }}</span>
                                @else
                                    <span class="badge text-bg-secondary">No company preference</span>
                                @endif
                                <span class="badge text-bg-light">{{ $listing->status?->value ? str($listing->status->value)->replace('_', ' ')->title() : 'Active' }}</span>
                                <span class="badge text-bg-light">{{ ucfirst($listing->listing_origin->value) }}</span>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                @if ($listing->company_id)
                                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="whitelist">
                                        <button type="submit" class="btn btn-sm {{ $preference?->value === 'whitelist' ? 'btn-warning' : 'btn-outline-warning' }}">
                                            <span aria-hidden="true">&#9733;</span>
                                            {{ $preference?->value === 'whitelist' ? 'Whitelisted' : 'Whitelist' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="verified">
                                        <button type="submit" class="btn btn-sm {{ $preference?->value === 'verified' ? 'btn-info' : 'btn-outline-info' }}">
                                            {{ $preference?->value === 'verified' ? 'Verified' : 'Verify' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="blacklist">
                                        <button type="submit" class="btn btn-sm {{ $preference?->value === 'blacklist' ? 'btn-danger' : 'btn-outline-danger' }}">
                                            {{ $preference?->value === 'blacklist' ? 'Blacklisted' : 'Blacklist' }}
                                        </button>
                                    </form>
                                @endif

                                <span class="border-start mx-1" aria-hidden="true"></span>

                                <form method="POST" action="{{ route('jobs.status', $listing) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="applied">
                                    <button type="submit" class="btn btn-sm {{ $listing->status?->value === 'applied' ? 'btn-success' : 'btn-outline-success' }}" @disabled($listing->status?->value === 'applied')>
                                        {{ $listing->status?->value === 'applied' ? 'Applied' : 'Mark applied' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('jobs.status', $listing) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="not_interested">
                                    <button type="submit" class="btn btn-sm {{ $listing->status?->value === 'not_interested' ? 'btn-secondary' : 'btn-outline-secondary' }}" @disabled($listing->status?->value === 'not_interested')>
                                        {{ $listing->status?->value === 'not_interested' ? 'Not interested' : 'Mark not interested' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        <dl class="small mb-0 text-md-end flex-shrink-0">
                            <dt class="visually-hidden">Posted</dt>
                            <dd class="mb-1">Posted: {{ $listing->posted_at?->format('M j, Y') ?? 'Unknown' }}</dd>
                            <dt class="visually-hidden">Salary</dt>
                            <dd class="mb-0">Salary: {{ $listing->salary ?: 'Not provided' }}</dd>
                        </dl>
                    </div>
                </div>
            </article>
        @empty
            <div class="alert alert-secondary">No jobs matched those filters. Try removing one or more requirements.</div>
        @endforelse

        @if ($listings->hasPages())
            <div class="mt-4">{{ $listings->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
        @endif
        @endif
    </main>
@endsection
