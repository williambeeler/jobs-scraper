<div class="btn-group" role="group" aria-label="Company actions">
    {{-- View button --}}
    <a href="{{ route('companies.show', $company->id) }}" 
       class="btn btn-sm btn-outline-secondary"
       title="View details">
        <i class="fa-solid fa-eye"></i>
    </a>

    {{-- Edit button --}}
    <a href="{{ route('companies.edit', $company->id) }}" 
       class="btn btn-sm btn-outline-primary"
       title="Edit company">
        <i class="fa-solid fa-pencil"></i>
    </a>

    <button
        type="button"
        class="btn btn-sm btn-outline-danger company-delete-btn"
        title="Delete company"
        data-company-id="{{ $company->id }}"
        data-company-name="{{ $company->name }}"
        data-delete-url="{{ route('companies.destroy', $company->id) }}"
    >
        <i class="fa-solid fa-trash"></i>
    </button>
</div>
