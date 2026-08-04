@extends('layouts.app')

@section('title', $company['name'] . ' | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 1100px;">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">CRM company</p>
                <h1 class="display-6 fw-bold mb-2">{{ $company['name'] }}</h1>
                <p class="text-secondary mb-0">Company details, activity, and classification.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ $company['edit_url'] }}">Edit company</a>
                <form method="POST" action="{{ $company['delete_url'] }}"
                    onsubmit="return confirm('Archive {{ addslashes($company['name']) }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Archive</button>
                </form>
            </div>
        </header>

        <div class="row g-4 mb-4">
            <div class="col-12 col-lg-8">
                <section class="card h-100 shadow-sm">
                    <div class="card-header bg-white p-4 pb-0 border-0">
                        <h2 class="h5 mb-1">Account details</h2>
                        <p class="small text-body-secondary mb-0">Contact, location, and opportunity information.</p>
                    </div>
                    <div class="card-body p-4">
                        <dl class="row mb-4">
                            @foreach (['alias' => 'Alias', 'website' => 'Website', 'email' => 'Email', 'phone' => 'Phone', 'poc_name' => 'Contact', 'poc_email' => 'Contact email', 'poc_phone' => 'Contact phone', 'source' => 'Source', 'city' => 'City', 'state' => 'State', 'postal_code' => 'Postal code', 'country' => 'Country', 'opportunity_score_index' => 'Opportunity score'] as $key => $label)
                                <dt class="col-sm-4 text-body-secondary fw-semibold">{{ $label }}</dt>
                                <dd class="col-sm-8 text-break">
                                    @if ($key === 'website' && data_get($company, $key))
                                        <a href="{{ data_get($company, $key) }}" target="_blank" rel="noopener noreferrer">
                                            {{ data_get($company, $key) }}</a>
                                    @elseif ($key === 'email' && data_get($company, $key))
                                        <a href="mailto:{{ data_get($company, $key) }}">{{ data_get($company, $key) }}</a>
                                    @else
                                        {{ data_get($company, $key) ?: '—' }}
                                    @endif
                                </dd>
                            @endforeach
                        </dl>
                        <h3 class="h6 fw-bold">Opportunity summary</h3>
                        <p class="mb-0 text-body-secondary" style="white-space: pre-wrap;">{{ $company['opportunity_summary'] ?: 'No summary yet.' }}</p>
                    </div>
                </section>
            </div>
            <aside class="col-12 col-lg-4 d-flex flex-column gap-4">
                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Classification</h2>
                        <form method="POST" action="{{ $company['classification_update_url'] }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="company-status">Status</label>
                                <select class="form-select" id="company-status" name="status_id" required>
                                    @foreach ($statusOptions as $option)
                                        <option value="{{ $option['id'] }}" @selected($company['status_id'] === $option['id'])>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="company-relationship">Relationship</label>
                                <select class="form-select" id="company-relationship" name="relationship_id" required>
                                    @foreach ($relationshipOptions as $option)
                                        <option value="{{ $option['id'] }}" @selected($company['relationship_id'] === $option['id'])>{{ $option['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-outline-primary" type="submit">Save classification</button>
                        </form>
                    </div>
                </section>
                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Tags and campaigns</h2>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse ($company['tags'] as $tag)
                                <span class="badge rounded-pill text-bg-secondary">{{ $tag }}</span>
                            @empty
                                @if (empty($company['campaigns']))
                                    <span class="text-body-secondary">None assigned.</span>
                                @endif
                            @endforelse
                            @foreach ($company['campaigns'] as $tag)
                                <span class="badge rounded-pill text-bg-info">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                </section>
            </aside>
        </div>

        <section class="card shadow-sm mb-4">
            <div class="card-header bg-white p-4 pb-0 border-0"><h2 class="h5 mb-0">Notes</h2></div>
            <div class="card-body p-4">
                <div class="list-group list-group-flush mb-4">
                    @forelse ($company['notes'] as $note)
                        <article class="list-group-item px-0 py-3">
                            <div>{!! $note['content'] !!}</div>
                            <small class="text-body-secondary">{{ $note['created_at_label'] }}</small>
                        </article>
                    @empty
                        <p class="text-body-secondary mb-0">No notes yet.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ $company['notes_store_url'] }}">
                    @csrf
                    <label class="form-label fw-semibold" for="content">Add note</label>
                    <textarea class="form-control mb-3" id="content" name="content" rows="4" required></textarea>
                    <div class="d-flex justify-content-end"><button class="btn btn-primary" type="submit">Save note</button></div>
                </form>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-header bg-white p-4 pb-0 border-0"><h2 class="h5 mb-0">Phone calls</h2></div>
            <div class="card-body p-4">
                <div class="list-group list-group-flush mb-4">
                    @forelse ($company['phone_calls'] as $call)
                        <article class="list-group-item px-0 py-3">
                            <p class="fw-semibold mb-1">{{ $call['called_at_label'] }}</p>
                            <p class="text-body-secondary mb-0" style="white-space: pre-wrap;">{{ $call['notes'] ?: 'No notes.' }}</p>
                        </article>
                    @empty
                        <p class="text-body-secondary mb-0">No calls logged.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ $company['phone_calls_store_url'] }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="called_at">Called at</label>
                            <input class="form-control" id="called_at" type="datetime-local" name="called_at" required>
                        </div>
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold" for="call-notes">Notes</label>
                            <textarea class="form-control" id="call-notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3"><button class="btn btn-outline-primary" type="submit">Log call</button></div>
                </form>
            </div>
        </section>
    </main>
@endsection
