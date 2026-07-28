@php($record = $company ?? [])
<div class="crm-grid">
    @foreach ([['name', 'Company name', 'text', 12], ['alias', 'Alias', 'text', 6], ['website', 'Website', 'url', 6], ['email', 'Company email', 'email', 6], ['phone', 'Phone', 'text', 6], ['source', 'Lead source', 'text', 6], ['poc_name', 'Contact name', 'text', 6], ['poc_email', 'Contact email', 'email', 6], ['poc_phone', 'Contact phone', 'text', 6], ['address_line1', 'Address line 1', 'text', 12], ['address_line2', 'Address line 2', 'text', 12], ['city', 'City', 'text', 4], ['state', 'State', 'text', 4], ['postal_code', 'Postal code', 'text', 4], ['country', 'Country', 'text', 6], ['opportunity_score_index', 'Opportunity score', 'number', 6]] as [$name, $label, $type, $span])
        <div class="crm-col-{{ $span }} crm-field">
            <label for="{{ $name }}">{{ $label }}</label>
            <input
                class="crm-input"
                id="{{ $name }}"
                name="{{ $name }}"
                type="{{ $type }}"
                @if ($type === 'number') min="0" max="100" @endif
                value="{{ old($name, data_get($record, $name)) }}"
                @required($name === 'name')>
            @error($name)
                <div class="crm-error">{{ $message }}</div>
            @enderror
        </div>
    @endforeach
    <div class="crm-col-6 crm-field">
        <label for="status_id">Status</label>
        <select
            class="crm-select"
            name="status_id"
            id="status_id">
            <option value="">Select status</option>
            @foreach ($statusOptions as $option)
                <option value="{{ $option['id'] }}" @selected((string) old('status_id', data_get($record, 'status_id')) === $option['id'])>
                    {{ $option['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="crm-col-6 crm-field">
        <label for="relationship_id">Relationship</label>
        <select
            class="crm-select"
            name="relationship_id"
            id="relationship_id">
            <option value="">Select relationship</option>
            @foreach ($relationshipOptions as $option)
                <option value="{{ $option['id'] }}" @selected((string) old('relationship_id', data_get($record, 'relationship_id')) === $option['id'])>
                    {{ $option['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="crm-col-6 crm-field">
        <label for="tags">Tags</label>
        <input
            class="crm-input"
            name="tags"
            id="tags"
            value="{{ old('tags', data_get($record, 'tags')) }}">
        <div class="crm-help">Comma-separated.</div>
    </div>
    <div class="crm-col-6 crm-field">
        <label for="campaigns">Campaigns</label>
        <input
            class="crm-input"
            name="campaigns"
            id="campaigns"
            value="{{ old('campaigns', data_get($record, 'campaigns')) }}">
        <div class="crm-help">Comma-separated.</div>
    </div>
    <div class="crm-col-6 crm-field">
        <label>
            <input
                type="checkbox"
                name="needs_callback"
                value="1"
                data-callback-toggle="#callback-at"
                @checked(old('needs_callback', data_get($record, 'needs_callback')))
            > Needs callback</label>
    </div>
    <div
        id="callback-at"
        class="crm-col-6 crm-field"
        @if (!old('needs_callback', data_get($record, 'needs_callback'))) hidden @endif>
        <label for="callback_at">Callback date</label>
        <input
            class="crm-input"
            type="datetime-local"
            name="callback_at"
            id="callback_at"
            value="{{ old('callback_at', data_get($record, 'callback_at')) }}">
    </div>
    <div class="crm-col-12 crm-field">
        <label for="opportunity_summary">Opportunity summary</label>
        <textarea
            class="crm-textarea"
            name="opportunity_summary"
            id="opportunity_summary"
        >{{ old('opportunity_summary', data_get($record, 'opportunity_summary')) }}</textarea>
    </div>
    <div class="crm-col-12 crm-field">
        <label for="meta">Meta (JSON)</label>
        <textarea
            class="crm-textarea"
            name="meta"
            id="meta"
        >{{ old('meta', data_get($record, 'meta')) }}</textarea>
    </div>
    <div class="crm-col-12 crm-field">
        <label
            for="{{ isset($company) ? 'new_note' : 'initial_note' }}">{{ isset($company) ? 'Add note' : 'Initial note' }}</label>
        <textarea
            class="crm-textarea"
            name="{{ isset($company) ? 'new_note' : 'initial_note' }}"
            id="{{ isset($company) ? 'new_note' : 'initial_note' }}">
</textarea>
    </div>
</div>
<div class="crm-actions">
    <button class="crm-button crm-button-primary">{{ $submitLabel }}</button>
</div>
