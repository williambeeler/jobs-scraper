<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $selectedDate->format('F j, Y') }} Jobs | {{ config('app.name') }}</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >
    </head>
    <body class="bg-light">
        <main class="container py-5">
            <header class="mb-4">
                <p class="mb-2 text-uppercase text-secondary fw-semibold small">
                    {{ $isToday ? "Today's scraped jobs" : 'Scraped jobs by date' }}
                </p>

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-2">
                    <h1 class="display-5 fw-bold mb-0">{{ $selectedDate->format('l, F j, Y') }}</h1>

                    <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2">
                        <label for="job-date" class="form-label mb-0 text-nowrap fw-semibold">Choose date</label>
                        <input
                            type="date"
                            class="form-control"
                            id="job-date"
                            name="date"
                            value="{{ $selectedDate->toDateString() }}"
                            aria-label="Choose a job scrape date"
                            onchange="this.form.submit()"
                        >
                        <noscript>
                            <button type="submit" class="btn btn-primary">View</button>
                        </noscript>
                    </form>
                </div>

                <p class="text-secondary mb-0">
                    {{ trans_choice(':count job listing|:count job listings', $listings->count(), ['count' => $listings->count()]) }}
                </p>
            </header>

            @if ($listings->isEmpty())
                <div class="alert alert-secondary" role="status">
                    No jobs were first scraped on {{ $selectedDate->format('F j, Y') }}.
                </div>
            @else
                <div class="list-group shadow-sm">
                    @foreach ($listings as $listing)
                        <a
                            href="{{ $listing->url }}"
                            class="list-group-item list-group-item-action p-4"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                <h2 class="h5 mb-0">{{ $listing->title }}</h2>
                                <span class="badge text-bg-primary align-self-start">{{ $listing->source }}</span>
                            </div>

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
                                <span>First scraped: {{ $listing->created_at?->timezone($displayTimezone)->format('g:i A') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </main>
    </body>
</html>
