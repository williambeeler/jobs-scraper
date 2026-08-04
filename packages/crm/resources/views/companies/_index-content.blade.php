<form class="card card-body shadow-sm mb-4" method="GET" action="{{ route('crm.companies.index') }}">
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="{{ request('search') }}"
                placeholder="Company, contact, website, email">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-semibold" for="status_id">Status</label>
            <select class="form-select" id="status_id" name="status_id">
                <option value="">All statuses</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status['id'] }}" @selected((string) request('status_id') === $status['id'])>
                        {{ $status['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-semibold" for="created_range">Created</label>
            <select class="form-select" id="created_range" name="created_range">
                @foreach (['all' => 'Any time', 'last_30' => 'Last 30 days', 'last_90' => 'Last 90 days', 'this_year' => 'This year'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('created_range', 'all') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-semibold" for="tag_filter">Tag</label>
            <select class="form-select" id="tag_filter" name="tag_filter">
                <option value="">All tags</option>
                @foreach ($tagOptions as $tag)
                    <option value="{{ $tag['name'] }}" @selected(request('tag_filter') === $tag['name'])>{{ $tag['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-semibold" for="campaign_filter">Campaign</label>
            <select class="form-select" id="campaign_filter" name="campaign_filter">
                <option value="">All campaigns</option>
                @foreach ($campaignOptions as $campaign)
                    <option value="{{ $campaign['name'] }}" @selected(request('campaign_filter') === $campaign['name'])>
                        {{ $campaign['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-semibold" for="per_page">Per page</label>
            <select class="form-select" id="per_page" name="per_page">
                @foreach ([20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2 align-items-end">
            <button class="btn btn-primary" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="{{ route('crm.companies.index') }}">Reset</a>
        </div>
    </div>
</form>

<form method="POST" action="{{ route('crm.companies.bulk-action') }}">
    @csrf
    <input type="hidden" name="search_term" value="{{ request('search') }}">
    <input type="hidden" name="status_id" value="{{ request('status_id') }}">
    <input type="hidden" name="tag_filter" value="{{ request('tag_filter') }}">
    <input type="hidden" name="campaign_filter" value="{{ request('campaign_filter') }}">
    <input type="hidden" name="created_range" value="{{ request('created_range', 'all') }}">

    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div class="form-check me-2">
                <input class="form-check-input" type="checkbox" id="select-all-companies"
                    onclick="document.querySelectorAll('[name=\'companies[]\']').forEach(company => company.checked = this.checked)">
                <label class="form-check-label" for="select-all-companies">Select all</label>
            </div>
            <select class="form-select form-select-sm w-auto" name="bulk_action" aria-label="Bulk action">
                <option value="">Bulk action</option>
                <option value="export_csv">Export CSV</option>
                <option value="export_excel">Export Excel</option>
                <option value="export_pdf">Export PDF</option>
                <option value="export_vcard">Export vCard</option>
                <option value="bulk_delete">Archive</option>
            </select>
            <select class="form-select form-select-sm w-auto" name="scope" aria-label="Bulk action scope">
                <option value="selected">Selected</option>
                <option value="filtered">All filtered</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Apply</button>
        </div>
        <span class="small text-body-secondary">
            {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }} of {{ number_format($companies->total()) }}
        </span>
    </div>

    @if ($companies->isEmpty())
        <div class="alert alert-secondary" role="status">No companies match these filters.</div>
    @else
        <div class="list-group shadow-sm">
            @foreach ($companies as $company)
                <article class="list-group-item p-4">
                    <div class="d-flex gap-3 align-items-start">
                        <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="companies[]"
                            value="{{ $company->id }}" aria-label="Select {{ $company->name }}">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 flex-grow-1">
                            <div class="flex-grow-1">
                                <h2 class="h5 mb-2">
                                    <a class="link-dark link-underline-opacity-0 link-underline-opacity-100-hover"
                                        href="{{ route('crm.companies.show', $company) }}">{{ $company->name }}</a>
                                </h2>

                                @if ($company->website)
                                    <p class="mb-2">
                                        <a class="text-body-secondary" href="{{ $company->website }}" target="_blank"
                                            rel="noopener noreferrer">{{ $company->website }}</a>
                                    </p>
                                @endif

                                @if ($company->tags->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        @foreach ($company->tags->where('type', 'tag') as $tag)
                                            <a class="badge rounded-pill text-bg-secondary text-decoration-none"
                                                href="{{ route('crm.companies.index', ['tag_filter' => $tag->name]) }}">{{ $tag->name }}</a>
                                        @endforeach
                                        @foreach ($company->tags->where('type', 'campaign') as $tag)
                                            <a class="badge rounded-pill text-bg-info text-decoration-none"
                                                href="{{ route('crm.companies.index', ['campaign_filter' => $tag->name]) }}">{{ $tag->name }}</a>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="d-flex flex-wrap gap-3 small text-body-secondary">
                                    <span>Location: {{ implode(', ', array_filter([$company->city, $company->state])) ?: 'Unknown' }}</span>
                                    <span>Opportunity score: {{ $company->opportunity_score_index ?? '—' }}</span>
                                </div>
                            </div>

                            <div class="d-flex flex-column align-items-start align-items-md-end gap-2 flex-shrink-0">
                                @if ($company->status)
                                    <a class="badge text-bg-primary text-decoration-none"
                                        href="{{ route('crm.companies.index', ['status_id' => $company->status_id]) }}">
                                        {{ $company->status->name }}
                                    </a>
                                @else
                                    <span class="badge text-bg-secondary">No status</span>
                                @endif
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('crm.companies.edit', $company) }}">Edit company</a>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($companies->hasPages())
        <div class="mt-4">{{ $companies->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
    @endif
</form>
