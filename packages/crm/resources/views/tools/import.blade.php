@extends('crm::layouts.app')

@section('title', 'Import Companies') @section('heading', 'Import Companies') @section('subtitle',
'Import a CSV into the CRM company directory.')

@section('content')@include('crm::partials.tools-tabs')
    <section class="crm-card">
        <div class="crm-actions">
            <a class="crm-button" href="{{ $templateDownloadUrl }}">Download
                Template</a>
            <span class="crm-muted">Maximum upload: {{ $maxUploadSize }}</span>
        </div>
        <form
            id="crm-import-form"
            method="POST"
            enctype="multipart/form-data"
            action="{{ route('crm.tools.import.companies') }}">
            @csrf<div class="crm-grid">
                <div class="crm-col-12 crm-field">
                    <label>CSV file</label>
                    <input
                        class="crm-input"
                        type="file"
                        name="file"
                        accept=".csv,text/csv"
                        required>
                </div>
                <div class="crm-col-6 crm-field">
                    <label>Status</label>
                    <select
                        class="crm-select"
                        name="status_id"
                        required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected($status->id === $defaultStatusId)>
                                {{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="crm-col-6 crm-field">
                    <label>Relationship</label>
                    <select
                        class="crm-select"
                        name="relationship_id"
                        required>
                        @foreach ($relationships as $relationship)
                            <option value="{{ $relationship->id }}" @selected($relationship->id === $leadRelationshipId)>
                                {{ $relationship->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="crm-col-4 crm-field">
                    <label>Source</label>
                    <input class="crm-input" name="source">
                </div>
                <div class="crm-col-4 crm-field">
                    <label>Tags</label>
                    <input
                        class="crm-input"
                        name="tags"
                        placeholder="Comma-separated">
                </div>
                <div class="crm-col-4 crm-field">
                    <label>Campaigns</label>
                    <input
                        class="crm-input"
                        name="campaigns"
                        placeholder="Comma-separated">
                </div>
            </div>
            <button class="crm-button crm-button-primary">Start Import</button>
        </form>
        <pre
            id="crm-import-output"
            class="crm-card"
            hidden>
</pre>
    </section>
@endsection

@push('scripts')
    <script>
        document.getElementById('crm-import-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const out = document.getElementById('crm-import-output');
            out.hidden = false;
            out.textContent = 'Importing…\n';
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    Accept: 'application/x-ndjson'
                }
            });
            const reader = response.body.getReader(),
                decoder = new TextDecoder();
            while (true) {
                const {
                    done,
                    value
                } = await reader.read();
                if (done) break;
                out.textContent += decoder.decode(value, {
                    stream: true
                });
            }
        });
    </script>
@endpush
