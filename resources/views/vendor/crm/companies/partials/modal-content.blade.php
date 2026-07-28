<div class="modal-header">
    <div>
        <h2 class="modal-title h4 mb-1">{{ $company->name }}</h2>
        <p class="text-muted mb-0">Company details, notes, and phone call history</p>
    </div>
    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="modal"
        aria-label="Close">
    </button>
</div>
<div class="modal-body">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-outline-primary btn-sm">
            Edit Company
        </a>
    </div>

    @include('crm::companies.partials.detail-cards', ['company' => $company])
    @include('crm::companies.partials.activity-panels', ['company' => $company])
</div>
