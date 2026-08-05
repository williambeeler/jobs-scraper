@php($record = $company ?? [])

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <p class="fw-semibold mb-2">Please correct the following:</p>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="card shadow-sm mb-4" aria-labelledby="company-details-heading">
    <div class="card-header bg-white p-4 pb-0 border-0">
        <h2 class="h5 mb-1" id="company-details-heading">Company details</h2>
        <p class="small text-body-secondary mb-0">The primary identity and source information for
            this account.</p>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @foreach ([['name', 'Company name', 'text', 12], ['alias', 'Alias', 'text', 6], ['website', 'Website', 'url', 6], ['email', 'Company email', 'email', 6], ['phone', 'Phone', 'text', 6], ['source', 'Lead source', 'text', 6], ['opportunity_score_index', 'Opportunity score', 'number', 6]] as [$name, $label, $type, $span])
                <div class="col-12 col-md-{{ $span }}">
                    <label class="form-label fw-semibold"
                        for="{{ $name }}">{{ $label }}</label>
                    <input
                        class="form-control @error($name) is-invalid @enderror"
                        id="{{ $name }}"
                        name="{{ $name }}"
                        type="{{ $type }}"
                        @if ($type === 'number') min="0" max="100" @endif
                        value="{{ old($name, data_get($record, $name)) }}"
                        @required($name === 'name')
                    >
                    @error($name)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="card shadow-sm mb-4" aria-labelledby="contact-heading">
    <div class="card-header bg-white p-4 pb-0 border-0">
        <h2 class="h5 mb-1" id="contact-heading">Contact and location</h2>
        <p class="small text-body-secondary mb-0">Who to contact and where the company is located.
        </p>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @foreach ([['poc_name', 'Contact name', 'text', 6], ['poc_email', 'Contact email', 'email', 6], ['poc_phone', 'Contact phone', 'text', 6], ['address_line1', 'Address line 1', 'text', 12], ['address_line2', 'Address line 2', 'text', 12], ['city', 'City', 'text', 5], ['state', 'State', 'text', 3], ['postal_code', 'Postal code', 'text', 4], ['country', 'Country', 'text', 6]] as [$name, $label, $type, $span])
                <div class="col-12 col-md-{{ $span }}">
                    <label class="form-label fw-semibold"
                        for="{{ $name }}">{{ $label }}</label>
                    <input
                        class="form-control @error($name) is-invalid @enderror"
                        id="{{ $name }}"
                        name="{{ $name }}"
                        type="{{ $type }}"
                        value="{{ old($name, data_get($record, $name)) }}"
                    >
                    @error($name)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="card shadow-sm mb-4" aria-labelledby="crm-details-heading">
    <div class="card-header bg-white p-4 pb-0 border-0">
        <h2 class="h5 mb-1" id="crm-details-heading">CRM details</h2>
        <p class="small text-body-secondary mb-0">Classify this company and keep its next steps
            visible.</p>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="status_id">Status</label>
                <select
                    class="form-select @error('status_id') is-invalid @enderror"
                    name="status_id"
                    id="status_id"
                >
                    <option value="">Select status</option>
                    @foreach ($statusOptions as $option)
                        <option value="{{ $option['id'] }}" @selected((string) old('status_id', data_get($record, 'status_id')) === $option['id'])>
                            {{ $option['name'] }}</option>
                    @endforeach
                </select>
                @error('status_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="relationship_id">Relationship</label>
                <select
                    class="form-select @error('relationship_id') is-invalid @enderror"
                    name="relationship_id"
                    id="relationship_id"
                >
                    <option value="">Select relationship</option>
                    @foreach ($relationshipOptions as $option)
                        <option value="{{ $option['id'] }}" @selected((string) old('relationship_id', data_get($record, 'relationship_id')) === $option['id'])>
                            {{ $option['name'] }}</option>
                    @endforeach
                </select>
                @error('relationship_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="tags">Tags</label>
                <input
                    class="form-control @error('tags') is-invalid @enderror"
                    name="tags"
                    id="tags"
                    value="{{ old('tags', data_get($record, 'tags')) }}"
                >
                <div class="form-text">Separate multiple tags with commas.</div>
                @error('tags')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="campaigns">Campaigns</label>
                <input
                    class="form-control @error('campaigns') is-invalid @enderror"
                    name="campaigns"
                    id="campaigns"
                    value="{{ old('campaigns', data_get($record, 'campaigns')) }}"
                >
                <div class="form-text">Separate multiple campaigns with commas.</div>
                @error('campaigns')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="needs_callback"
                        name="needs_callback"
                        value="1"
                        data-callback-toggle="#callback-at"
                        @checked(old('needs_callback', data_get($record, 'needs_callback')))
                    >
                    <label class="form-check-label fw-semibold" for="needs_callback">Needs
                        callback</label>
                </div>
            </div>
            <div
                class="col-12 col-md-6"
                id="callback-at"
                @if (!old('needs_callback', data_get($record, 'needs_callback'))) hidden @endif
            >
                <label class="form-label fw-semibold" for="callback_at">Callback date</label>
                <input
                    class="form-control @error('callback_at') is-invalid @enderror"
                    type="datetime-local"
                    name="callback_at"
                    id="callback_at"
                    value="{{ old('callback_at', data_get($record, 'callback_at')) }}"
                >
                @error('callback_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</section>

<section class="card shadow-sm mb-4" aria-labelledby="context-heading">
    <div class="card-header bg-white p-4 pb-0 border-0">
        <h2 class="h5 mb-1" id="context-heading">Context and notes</h2>
        <p class="small text-body-secondary mb-0">Capture the opportunity, structured metadata, and
            a new activity note.</p>
    </div>
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold" for="opportunity_summary">Opportunity
                summary</label>
            <textarea
                class="form-control @error('opportunity_summary') is-invalid @enderror"
                rows="5"
                name="opportunity_summary"
                id="opportunity_summary"
            >{{ old('opportunity_summary', data_get($record, 'opportunity_summary')) }}</textarea>
            @error('opportunity_summary')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="meta">Meta <span
                    class="fw-normal text-body-secondary"
                >(JSON)</span></label>
            <textarea
                class="form-control font-monospace @error('meta') is-invalid @enderror"
                rows="5"
                name="meta"
                id="meta"
            >{{ old('meta', data_get($record, 'meta')) }}</textarea>
            @error('meta')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <label class="form-label fw-semibold"
                for="{{ isset($company) ? 'new_note' : 'initial_note' }}"
            >{{ isset($company) ? 'Add note' : 'Initial note' }}</label>
            <textarea
                class="form-control"
                rows="5"
                name="{{ isset($company) ? 'new_note' : 'initial_note' }}"
                id="{{ isset($company) ? 'new_note' : 'initial_note' }}"
            ></textarea>
        </div>
    </div>
</section>

<div class="d-flex flex-wrap justify-content-end gap-2">
    <a class="btn btn-outline-secondary" href="{{ route('crm.companies.index') }}">Cancel</a>
    <button class="btn btn-primary px-4" type="submit">{{ $submitLabel }}</button>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('[data-callback-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const target = document.querySelector(toggle.dataset.callbackToggle);
                if (target) target.hidden = !toggle.checked;
            });
        });
    </script>
@endpush
