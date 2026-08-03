@extends('layouts.app')

@section('title', 'Export Companies') @section('heading', 'Export Companies') @section('subtitle',
'Build a downloadable export of CRM company data.')

@section('content')@include('crm::partials.tools-tabs')
    <section class="crm-card">
        <h2>{{ number_format($companyCount) }} companies ready to export</h2>
        <form
            id="crm-export-form"
            action="{{ route('crm.tools.exports.start') }}"
            method="POST">
            @csrf<div class="crm-field">
                <label>Export type</label>
                <select
                    class="crm-select"
                    name="export_type"
                    required>
                    @foreach ($exportOptions as $key => $option)
                        <option value="{{ is_string($key) ? $key : data_get($option, 'value') }}">
                            {{ is_string($option) ? $option : data_get($option, 'label') }}</option>
                    @endforeach
                </select>
            </div>
            <button class="crm-button crm-button-primary">Start Export</button>
        </form>
        <p id="crm-export-status" class="crm-muted">
        </p>
    </section>
@endsection

@push('scripts')
    <script>
        document.getElementById('crm-export-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const status = document.getElementById('crm-export-status');
            status.textContent = 'Preparing export…';
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    Accept: 'application/json'
                }
            });
            const data = await response.json();
            if (!response.ok) {
                status.textContent = data.message || 'Export failed.';
                return
            }
            const poll = async () => {
                const check = await fetch(
                    '{{ url(config('crm.route_prefix', 'crm') . '/tools/exports') }}/' +
                    data.export_id + '/status');
                const current = await check.json();
                status.textContent =
                    `${current.processed ?? 0} of ${current.total ?? 0} processed`;
                if (current.download_url) {
                    status.innerHTML =
                        '<a class="crm-button crm-button-primary" href="' + current
                        .download_url + '">Download Export</a>'
                } else if (current.status === 'failed') {
                    status.textContent = current.error || 'Export failed.'
                } else setTimeout(poll, 1000)
            };
            poll()
        });
    </script>
@endpush
