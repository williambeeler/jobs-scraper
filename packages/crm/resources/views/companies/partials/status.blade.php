@if($company->status)
    <a
        href="{{ route('companies.index', ['status_id' => $company->status_id]) }}"
        class="badge rounded-pill px-3 py-2 text-decoration-none"
        style="background-color: {{ $company->status->color ?? '#6c757d' }}"
    >
        {{ $company->status->name }}
    </a>
@else
    <span class="badge bg-secondary">No Status</span>
@endif
