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
                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}" data-company-preference-form>
                        @csrf
                        <input type="hidden" name="type" value="whitelist">
                        <button type="submit" data-company-preference-button class="btn btn-sm {{ $listing->is_whitelisted ? 'btn-warning' : 'btn-outline-warning' }}">
                            <span aria-hidden="true">&#9733;</span>
                            <span data-whitelist-label>{{ $listing->is_whitelisted ? 'Whitelisted' : 'Whitelist' }}</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}" data-company-preference-form>
                        @csrf
                        <input type="hidden" name="type" value="verified">
                        <button type="submit" data-company-preference-button class="btn btn-sm {{ $listing->is_verified ? 'btn-info' : 'btn-outline-info' }}">Verified</button>
                    </form>
                    <form method="POST" action="{{ route('jobs.company-preference', $listing) }}" data-company-preference-form>
                        @csrf
                        <input type="hidden" name="type" value="blacklist">
                        <button type="submit" data-company-preference-button class="btn btn-sm btn-outline-secondary">Blacklist</button>
                    </form>
                </div>
            @endif

            <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                <span>Posted: {{ $listing->posted_age ?? 'Unknown' }}</span>
                @if ($listing->salary)
                    <span>Salary: {{ $listing->salary }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column align-items-md-end gap-2 flex-shrink-0">
            <span
                class="job-row-star {{ $listing->is_whitelisted ? '' : 'd-none' }}"
                title="Whitelisted company"
                aria-label="Whitelisted company"
                data-job-row-star
            >&#9733;</span>
            <span class="badge text-bg-primary align-self-start align-self-md-end">{{ $listing->source }}</span>

            <form method="POST" action="{{ route('jobs.status', $listing) }}" data-job-status-form>
                @csrf
                <input type="hidden" name="status" value="not_interested">
                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                    <span aria-hidden="true">&#x1F6D1;</span> Not Interested
                </button>
            </form>

            <form method="POST" action="{{ route('jobs.status', $listing) }}" data-job-status-form>
                @csrf
                <input type="hidden" name="status" value="applied">
                <button type="submit" class="btn btn-sm btn-success w-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path d="M13.485 1.929a.75.75 0 0 1 .086 1.056l-7 8a.75.75 0 0 1-1.113.02l-3-3a.75.75 0 0 1 1.06-1.06l2.434 2.433 6.477-7.363a.75.75 0 0 1 1.056-.086Z"/>
                    </svg>
                    Applied
                </button>
            </form>
        </div>
    </div>
</div>
