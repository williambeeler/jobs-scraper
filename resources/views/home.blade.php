@extends('layouts.app')

@section('title', $selectedDate->format('F j, Y') . ' Jobs | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">
                {{ $isToday ? "Today's scraped jobs" : 'Scraped jobs by date' }}
            </p>

            <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                <h1 class="display-5 fw-bold mb-0">{{ $selectedDate->format('l, F j, Y') }}</h1>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#clear-daily-jobs-modal"
                    @disabled($listings->isEmpty())>
                    Not interested in all
                </button>
            </div>

            <p
                class="text-secondary mb-0"
                id="job-count"
                data-count="{{ $listings->count() }}"
                aria-live="polite">
                {{ trans_choice(':count job listing|:count job listings', $listings->count(), ['count' => $listings->count()]) }}
            </p>
        </header>

        <div
            class="modal fade"
            id="clear-daily-jobs-modal"
            tabindex="-1"
            aria-labelledby="clear-daily-jobs-modal-title"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="clear-daily-jobs-modal-title">
                            Mark all jobs as not interested?
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        This will mark all {{ $listings->count() }} jobs listed for
                        {{ $selectedDate->format('F j, Y') }} as not interested and remove them from this list.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form
                            method="POST"
                            action="{{ route('jobs.daily.not-interested', $selectedDate->toDateString()) }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">Mark all not interested</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="alert alert-secondary {{ $listings->isEmpty() ? '' : 'd-none' }}"
            id="job-list-empty-state"
            role="status">
            No jobs were first scraped on {{ $selectedDate->format('F j, Y') }}.
        </div>

        <div class="list-group shadow-sm {{ $listings->isEmpty() ? 'd-none' : '' }}" id="job-list">
            @foreach ($listings as $listing)
                <div class="list-group-item p-4 {{ $listing->is_whitelisted ? 'job-row-whitelisted' : ($listing->is_verified ? 'job-row-verified' : '') }}"
                    data-job-row
                    data-company-id="{{ $listing->company_id }}">
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
                                @if ($listing->company_search_url)
                                    <a href="{{ $listing->company_search_url }}" target="_blank" rel="noopener noreferrer">{{ $listing->company->name }}</a>
                                @else
                                    Unknown company
                                @endif
                                @if ($listing->location)
                                    <span aria-hidden="true">&middot;</span> {{ $listing->location }}
                                @endif
                            </p>

                            @if ($listing->company_id)
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <form method="POST"
                                        action="{{ route('jobs.company-preference', $listing) }}"
                                        data-company-preference-form>
                                        @csrf
                                        <input
                                            type="hidden"
                                            name="type"
                                            value="whitelist">
                                        <button type="submit" data-company-preference-button
                                            class="btn btn-sm {{ $listing->is_whitelisted ? 'btn-warning' : 'btn-outline-warning' }}">
                                            <span aria-hidden="true">&#9733;</span>
                                            <span data-whitelist-label>{{ $listing->is_whitelisted ? 'Whitelisted' : 'Whitelist' }}</span>
                                        </button>
                                    </form>
                                    <form method="POST"
                                        action="{{ route('jobs.company-preference', $listing) }}"
                                        data-company-preference-form>
                                        @csrf
                                        <input
                                            type="hidden"
                                            name="type"
                                            value="verified">
                                        <button type="submit" data-company-preference-button
                                            class="btn btn-sm {{ $listing->is_verified ? 'btn-info' : 'btn-outline-info' }}">
                                            Verified
                                        </button>
                                    </form>
                                    <form method="POST"
                                        action="{{ route('jobs.company-preference', $listing) }}"
                                        data-company-preference-form>
                                        @csrf
                                        <input
                                            type="hidden"
                                            name="type"
                                            value="blacklist">
                                        <button type="submit" data-company-preference-button
                                            class="btn btn-sm btn-outline-secondary">Blacklist</button>
                                    </form>
                                </div>
                            @endif

                            <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                <span>Posted: {{ $listing->posted_age ?? 'Unknown' }}</span>
                                @if ($listing->salary)
                                    <span>Salary: {{ $listing->salary }}</span>
                                @endif
                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-link px-0 mt-2"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-listing-{{ $listing->id }}">
                                Edit details &amp; notes
                            </button>
                        </div>

                        <div class="d-flex flex-column align-items-md-end gap-2 flex-shrink-0">
                            <span
                                class="job-row-star {{ $listing->is_whitelisted ? '' : 'd-none' }}"
                                title="Whitelisted company"
                                aria-label="Whitelisted company"
                                data-job-row-star
                            >&#9733;</span>
                            <span
                                class="badge text-bg-primary align-self-start align-self-md-end">{{ $listing->source }}</span>

                            <form
                                method="POST"
                                action="{{ route('jobs.status', $listing) }}"
                                data-job-status-form>
                                @csrf
                                <input
                                    type="hidden"
                                    name="status"
                                    value="not_interested">
                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                    <span aria-hidden="true">&#x1F6D1;</span> Not Interested
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="{{ route('jobs.status', $listing) }}"
                                data-job-status-form>
                                @csrf
                                <input
                                    type="hidden"
                                    name="status"
                                    value="applied">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="14"
                                        height="14"
                                        viewBox="0 0 16 16"
                                        fill="currentColor"
                                        aria-hidden="true">
                                        <path
                                            d="M13.485 1.929a.75.75 0 0 1 .086 1.056l-7 8a.75.75 0 0 1-1.113.02l-3-3a.75.75 0 0 1 1.06-1.06l2.434 2.433 6.477-7.363a.75.75 0 0 1 1.056-.086Z"/>
                                    </svg>
                                    Applied
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @foreach ($listings as $listing)
            <div
                class="modal fade"
                id="edit-listing-{{ $listing->id }}"
                tabindex="-1"
                aria-labelledby="edit-listing-{{ $listing->id }}-title"
                aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="edit-listing-{{ $listing->id }}-title">
                                {{ $listing->title }}</h2>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <dl class="row mb-4">
                                <dt class="col-sm-3">Job title</dt>
                                <dd class="col-sm-9">{{ $listing->title }}</dd>
                                <dt class="col-sm-3">Company</dt>
                                <dd class="col-sm-9">
                                    @if ($listing->company_search_url)
                                        <a href="{{ $listing->company_search_url }}" target="_blank" rel="noopener noreferrer">{{ $listing->company->name }}</a>
                                    @else
                                        Not provided
                                    @endif
                                </dd>
                                <dt class="col-sm-3">Location</dt>
                                <dd class="col-sm-9">{{ $listing->location ?: 'Not provided' }}</dd>
                                <dt class="col-sm-3">Job URL</dt>
                                <dd class="col-sm-9 text-break"><a
                                        href="{{ $listing->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ $listing->url }}</a></dd>
                                <dt class="col-sm-3">Source</dt>
                                <dd class="col-sm-9">{{ $listing->source }}</dd>
                                <dt class="col-sm-3">Posted date</dt>
                                <dd class="col-sm-9">
                                    {{ $listing->posted_at?->format('F j, Y') ?? 'Not provided' }}</dd>
                            </dl>

                            <form method="POST"
                                action="{{ route('jobs.details.update', $listing) }}">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="listing-{{ $listing->id }}-salary"
                                            class="form-label"
                                        >Salary</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="listing-{{ $listing->id }}-salary"
                                            name="salary"
                                            value="{{ $listing->salary }}">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary mt-3">Save
                                    salary</button>
                            </form>

                            <hr class="my-4">

                            <section aria-labelledby="listing-{{ $listing->id }}-notes-title">
                                <h3 class="h5" id="listing-{{ $listing->id }}-notes-title">Notes
                                </h3>

                                @if ($listing->notes->isEmpty())
                                    <p class="text-body-secondary">No notes yet.</p>
                                @else
                                    <div class="list-group mb-3">
                                        @foreach ($listing->notes as $note)
                                            <div
                                                class="list-group-item d-flex justify-content-between gap-3">
                                                <div>
                                                    <div class="mb-1 text-break note-content">
                                                        {!! $note->body !!}</div>
                                                    <small
                                                        class="text-body-secondary">{{ $note->created_at?->diffForHumans() }}</small>
                                                </div>
                                                <form method="POST"
                                                    action="{{ route('jobs.notes.destroy', [$listing, $note]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                    >Delete</button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <form method="POST"
                                    action="{{ route('jobs.notes.store', $listing) }}">
                                    @csrf
                                    <label for="listing-{{ $listing->id }}-note"
                                        class="form-label">Add note</label>
                                    <div class="note-editor border rounded" data-note-editor>
                                        <div
                                            class="note-editor-toolbar d-flex flex-wrap gap-1 border-bottom bg-body-tertiary p-2"
                                            role="toolbar"
                                            aria-label="Note formatting">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="heading"
                                            >Heading</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light fw-bold"
                                                data-note-command="bold"
                                                aria-label="Bold"
                                            >B</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light fst-italic"
                                                data-note-command="italic"
                                                aria-label="Italic"
                                            >I</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light text-decoration-underline"
                                                data-note-command="underline"
                                                aria-label="Underline"
                                            >U</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-warning"
                                                data-note-command="highlight"
                                                aria-label="Highlight"
                                            >Highlight</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="bulletList"
                                            >Bullets</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="orderedList"
                                            >Numbers</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="blockquote"
                                            >Quote</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="codeBlock"
                                            >Code</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="link"
                                            >Link</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="undo"
                                            >Undo</button>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light"
                                                data-note-command="redo"
                                            >Redo</button>
                                        </div>
                                        <div data-note-surface></div>
                                        <textarea
                                            class="visually-hidden"
                                            id="listing-{{ $listing->id }}-note"
                                            name="body"
                                            maxlength="10000"
                                            data-note-input
                                        ></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-outline-primary mt-2">Add
                                        note</button>
                                </form>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </main>
@endsection

@push('scripts')
    @vite('resources/js/notes.ts')
    <script>
        const updateVisibleJobCount = (removedCount) => {
            const countElement = document.getElementById('job-count');
            const list = document.getElementById('job-list');
            const emptyState = document.getElementById('job-list-empty-state');
            const count = Math.max(0, Number(countElement.dataset.count) - removedCount);

            countElement.dataset.count = String(count);
            countElement.textContent = `${count} job ${count === 1 ? 'listing' : 'listings'}`;

            if (count === 0) {
                list.classList.add('d-none');
                emptyState.classList.remove('d-none');
            }
        };

        const rowsForCompany = (companyId) => Array.from(
            document.querySelectorAll('[data-job-row]'),
        ).filter((row) => row.dataset.companyId === String(companyId));

        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-company-preference-form]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const sourceRow = form.closest('[data-job-row]');
            const companyRows = rowsForCompany(sourceRow.dataset.companyId);
            const buttons = companyRows.flatMap((row) => Array.from(
                row.querySelectorAll('[data-company-preference-button]'),
            ));

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
                    throw new Error(`Company preference update failed with HTTP ${response.status}`);
                }

                const preference = await response.json();

                if (preference.type === 'blacklist') {
                    companyRows.forEach((row) => row.remove());
                    updateVisibleJobCount(companyRows.length);

                    return;
                }

                companyRows.forEach((row) => {
                    const isWhitelisted = preference.type === 'whitelist';
                    const isVerified = preference.type === 'verified';
                    const whitelistButton = row.querySelector('input[value="whitelist"]')
                        .closest('form').querySelector('button');
                    const verifiedButton = row.querySelector('input[value="verified"]')
                        .closest('form').querySelector('button');

                    row.classList.toggle('job-row-whitelisted', isWhitelisted);
                    row.classList.toggle('job-row-verified', isVerified);
                    row.querySelector('[data-job-row-star]').classList.toggle('d-none', !isWhitelisted);

                    whitelistButton.classList.toggle('btn-warning', isWhitelisted);
                    whitelistButton.classList.toggle('btn-outline-warning', !isWhitelisted);
                    whitelistButton.querySelector('[data-whitelist-label]').textContent =
                        isWhitelisted ? 'Whitelisted' : 'Whitelist';
                    verifiedButton.classList.toggle('btn-info', isVerified);
                    verifiedButton.classList.toggle('btn-outline-info', !isVerified);
                });
            } catch (error) {
                window.alert('The company preference could not be saved. Please try again.');
                console.error(error);
            } finally {
                buttons.forEach((button) => {
                    button.disabled = false;
                });
            }
        });

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
                    updateVisibleJobCount(1);
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

@push('styles')
    <style>
        .note-editor-surface {
            min-height: 10rem;
            padding: .75rem;
            outline: none;
        }

        .note-editor-surface> :last-child,
        .note-content> :last-child {
            margin-bottom: 0;
        }

        .note-editor-surface mark,
        .note-content mark {
            background-color: #fff176;
            border-radius: .125rem;
            padding: 0 .1em;
        }
    </style>
@endpush
