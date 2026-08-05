<div class="crm-company-name-cell">
    <a
        href="{{ route('crm.companies.show', $company) }}"
        class="fw-semibold text-decoration-none crm-company-link crm-company-name-link"
        data-modal-url="{{ route('crm.companies.modal', $company) }}"
    >
        {{ $company->name }}
    </a>

    <div class="crm-company-name-actions" aria-label="Company actions">
        <a href="{{ route('crm.companies.edit', $company) }}" class="btn btn-sm btn-outline-primary">
            Edit
        </a>
        <a href="{{ route('crm.companies.show', $company) }}"
            class="btn btn-sm btn-outline-secondary">
            View
        </a>
        <button
            type="button"
            class="btn btn-sm btn-outline-danger company-delete-btn"
            data-company-id="{{ $company->id }}"
            data-company-name="{{ $company->name }}"
            data-delete-url="{{ route('crm.companies.destroy', $company->id) }}"
        >
            Delete
        </button>
    </div>
</div>
