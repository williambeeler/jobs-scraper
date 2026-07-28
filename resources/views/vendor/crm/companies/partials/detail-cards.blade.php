@php
    $availableStatuses = $availableStatuses ?? \WilliamBeeler\Crm\Models\Status::orderBy('name')->get();
    $availableRelationships = $availableRelationships ?? \WilliamBeeler\Crm\Models\Relationship::orderBy('name')->get();
@endphp

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <strong>Company Information</strong>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-md-3">Alias</dt>
            <dd class="col-md-9">{{ $company->alias ?? '—' }}</dd>

            <dt class="col-md-3">Website</dt>
            <dd class="col-md-9">
                @if($company->website)
                    <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">{{ $company->website }}</a>
                @else
                    —
                @endif
            </dd>

            <dt class="col-md-3">Email</dt>
            <dd class="col-md-9">{{ $company->email ?? '—' }}</dd>

            <dt class="col-md-3">Phone</dt>
            <dd class="col-md-9">
                @if($company->phone)
                    @php
                        $voiceNumber = preg_replace('/\D+/', '', $company->phone) ?? '';
                        $voiceNumber = str_starts_with($voiceNumber, '1') ? '+' . $voiceNumber : '+1' . $voiceNumber;
                        $googleVoiceUrl = 'https://voice.google.com/u/0/calls?a=nc,' . rawurlencode($voiceNumber);
                    @endphp
                    <a
                        href="{{ $googleVoiceUrl }}"
                        class="btn btn-outline-primary btn-sm fw-semibold"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ $company->phone }}
                    </a>
                @else
                    —
                @endif
            </dd>

            <dt class="col-md-3">Call Back</dt>
            <dd class="col-md-9">
                <form
                    method="POST"
                    action="{{ route('companies.callback.update', $company) }}"
                    class="crm-callback-form"
                >
                    @csrf
                    @method('PATCH')
                    <div class="form-check form-switch mb-3">
                        <input
                            class="form-check-input crm-callback-toggle"
                            type="checkbox"
                            role="switch"
                            id="company-callback-toggle-{{ $company->id }}"
                            name="needs_callback"
                            value="1"
                            @checked($company->needs_callback)
                        >
                        <label class="form-check-label fw-semibold" for="company-callback-toggle-{{ $company->id }}">
                            Call back
                        </label>
                    </div>

                    <div class="crm-callback-date-wrap">
                        <label for="company-callback-at-{{ $company->id }}" class="form-label">Call Back Date</label>
                        <input
                            type="datetime-local"
                            id="company-callback-at-{{ $company->id }}"
                            name="callback_at"
                            class="form-control crm-local-datetime-default"
                            value="{{ $company->callback_at?->format('Y-m-d\\TH:i') }}"
                        >
                    </div>
                </form>
            </dd>

            <dt class="col-md-3">Tags</dt>
            <dd class="col-md-9">
                @forelse($company->tagsWithType('tag') as $tag)
                    <span class="badge bg-primary">{{ $tag->name }}</span>
                @empty
                    —
                @endforelse
            </dd>

            <dt class="col-md-3">Campaigns</dt>
            <dd class="col-md-9">
                @forelse($company->tagsWithType('campaign') as $campaign)
                    <span class="badge bg-info text-dark">{{ $campaign->name }}</span>
                @empty
                    —
                @endforelse
            </dd>

            <dt class="col-md-3">Source</dt>
            <dd class="col-md-9">{{ $company->source ?? '—' }}</dd>

            <dt class="col-md-3">Classification</dt>
            <dd class="col-md-9">
                <form
                    method="POST"
                    action="{{ route('companies.classification.update', $company) }}"
                    class="crm-company-classification-form"
                >
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label for="company-status-{{ $company->id }}" class="form-label">Status</label>
                            <select id="company-status-{{ $company->id }}" name="status_id" class="form-select">
                                @foreach($availableStatuses as $statusOption)
                                    <option value="{{ $statusOption->id }}" @selected($company->status_id === $statusOption->id)>
                                        {{ $statusOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($company->status?->description)
                                <small class="text-muted d-block mt-1">{{ $company->status->description }}</small>
                            @endif
                        </div>

                        <div class="col-12 col-lg-6">
                            <label for="company-relationship-{{ $company->id }}" class="form-label">Relationship</label>
                            <select id="company-relationship-{{ $company->id }}" name="relationship_id" class="form-select">
                                @foreach($availableRelationships as $relationshipOption)
                                    <option value="{{ $relationshipOption->id }}" @selected($company->relationship_id === $relationshipOption->id)>
                                        {{ $relationshipOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($company->relationship?->description)
                                <small class="text-muted d-block mt-1">{{ $company->relationship->description }}</small>
                            @endif
                        </div>
                    </div>
                </form>
            </dd>

            <dt class="col-md-3">Opportunity Score Index</dt>
            <dd class="col-md-9">{{ is_null($company->opportunity_score_index) ? '—' : $company->opportunity_score_index }}</dd>

            <dt class="col-md-3">Opportunity Summary</dt>
            <dd class="col-md-9" style="white-space: pre-wrap;">{{ $company->opportunity_summary ?? '—' }}</dd>
        </dl>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <strong>Primary Contact</strong>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-md-3">Name</dt>
            <dd class="col-md-9">{{ $company->poc_name ?? '—' }}</dd>

            <dt class="col-md-3">Email</dt>
            <dd class="col-md-9">{{ $company->poc_email ?? '—' }}</dd>

            <dt class="col-md-3">Phone</dt>
            <dd class="col-md-9">{{ $company->poc_phone ?? '—' }}</dd>
        </dl>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <strong>Address</strong>
    </div>
    <div class="card-body">
        @if($company->address_line1 || $company->city)
            <p class="mb-0">
                {{ $company->address_line1 }}<br>
                @if($company->address_line2)
                    {{ $company->address_line2 }}<br>
                @endif
                {{ $company->city }}, {{ $company->state }} {{ $company->postal_code }}<br>
                {{ $company->country }}
            </p>
        @else
            <p class="text-muted mb-0">No address on file.</p>
        @endif
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-dark text-white">
        <strong>Additional Data</strong>
    </div>
    <div class="card-body">
        @if(!empty($company->meta))
            <pre class="bg-light p-3 rounded mb-0">{{ json_encode($company->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <p class="text-muted mb-0">No meta data.</p>
        @endif
    </div>
</div>
