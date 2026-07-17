@extends('layouts.app')

@section('title', $selectedDate->format('F j, Y').' Jobs | '.config('app.name'))

@section('content')
    <main class="container py-5">
            <header class="mb-4">
                <p class="mb-2 text-uppercase text-secondary fw-semibold small">
                    {{ $isToday ? "Today's scraped jobs" : 'Scraped jobs by date' }}
                </p>

                <h1 class="display-5 fw-bold mb-2">{{ $selectedDate->format('l, F j, Y') }}</h1>

                <p
                    class="text-secondary mb-0"
                    id="job-count"
                    data-count="{{ $listings->count() }}"
                    aria-live="polite"
                >
                    {{ trans_choice(':count job listing|:count job listings', $listings->count(), ['count' => $listings->count()]) }}
                </p>
            </header>

            <div
                class="alert alert-secondary {{ $listings->isEmpty() ? '' : 'd-none' }}"
                id="job-list-empty-state"
                role="status"
            >
                No jobs were first scraped on {{ $selectedDate->format('F j, Y') }}.
            </div>

            <div
                class="list-group shadow-sm {{ $listings->isEmpty() ? 'd-none' : '' }}"
                id="job-list"
            >
                @foreach ($listings as $listing)
                        <div class="list-group-item p-4 {{ $listing->is_whitelisted ? 'job-row-whitelisted' : '' }}" data-job-row>
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div class="flex-grow-1">
                                    <h2 class="h5 mb-2">
                                        <a
                                            href="{{ $listing->url }}"
                                            class="link-dark link-underline-opacity-0 link-underline-opacity-100-hover"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >{{ $listing->title }}</a>
                                    </h2>

                                    <p class="mb-2 text-body-secondary">
                                        {{ $listing->company ?: 'Unknown company' }}
                                        @if ($listing->location)
                                            <span aria-hidden="true">&middot;</span> {{ $listing->location }}
                                        @endif
                                    </p>

                                    @if ($listing->company)
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <form method="POST" action="{{ route('jobs.company-preference', $listing) }}">
                                                @csrf
                                                <input type="hidden" name="type" value="whitelist">
                                                <button type="submit" class="btn btn-sm {{ $listing->is_whitelisted ? 'btn-warning' : 'btn-outline-warning' }}">
                                                    <span aria-hidden="true">&#9733;</span>
                                                    {{ $listing->is_whitelisted ? 'Whitelisted' : 'Whitelist company' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('jobs.company-preference', $listing) }}">
                                                @csrf
                                                <input type="hidden" name="type" value="blacklist">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    Blacklist company
                                                </button>
                                            </form>
                                        </div>
                                    @endif

                                    <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                        <span>Posted: {{ $listing->posted_relative ?: ($listing->posted_at?->format('M j, Y') ?? 'Unknown') }}</span>
                                        @if ($listing->salary)
                                            <span>Salary: {{ $listing->salary }}</span>
                                        @endif
                                        <span>First scraped: {{ $listing->created_at?->timezone($displayTimezone)->format('g:i A') }}</span>
                                    </div>
                                </div>

                                <div class="d-flex flex-column align-items-md-end gap-2 flex-shrink-0">
                                    @if ($listing->is_whitelisted)
                                        <span class="job-row-star" title="Whitelisted company" aria-label="Whitelisted company">&#9733;</span>
                                    @endif
                                    <span class="badge text-bg-primary align-self-start align-self-md-end">{{ $listing->source }}</span>

                                    <form
                                        method="POST"
                                        action="{{ route('jobs.status', $listing) }}"
                                        data-job-status-form
                                    >
                                        @csrf
                                        <input type="hidden" name="status" value="not_interested">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            <span aria-hidden="true">&#x1F6D1;</span> Not Interested
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('jobs.status', $listing) }}"
                                        data-job-status-form
                                    >
                                        @csrf
                                        <input type="hidden" name="status" value="applied">
                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 16 16"
                                                fill="currentColor"
                                                aria-hidden="true"
                                            >
                                                <path d="M13.485 1.929a.75.75 0 0 1 .086 1.056l-7 8a.75.75 0 0 1-1.113.02l-3-3a.75.75 0 0 1 1.06-1.06l2.434 2.433 6.477-7.363a.75.75 0 0 1 1.056-.086Z"/>
                                            </svg>
                                            Applied
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                @endforeach
            </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-job-status-form]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const row = form.closest('[data-job-row]');
            const buttons = row.querySelectorAll('button');

            buttons.forEach((button) => {
                button.disabled = true;
            });

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Status update failed with HTTP ${response.status}`);
                }

                row.style.transition = 'opacity 150ms ease, transform 150ms ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(12px)';

                window.setTimeout(() => {
                    row.remove();

                    const countElement = document.getElementById('job-count');
                    const list = document.getElementById('job-list');
                    const emptyState = document.getElementById('job-list-empty-state');
                    const count = Math.max(0, Number(countElement.dataset.count) - 1);

                    countElement.dataset.count = String(count);
                    countElement.textContent = `${count} job ${count === 1 ? 'listing' : 'listings'}`;

                    if (count === 0) {
                        list.classList.add('d-none');
                        emptyState.classList.remove('d-none');
                    }
                }, 150);
            } catch (error) {
                buttons.forEach((button) => {
                    button.disabled = false;
                });

                window.alert('The job decision could not be saved. Please try again.');
                console.error(error);
            }
        });
    </script>
@endpush
