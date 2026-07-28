{{-- Do nothing yet --}}

@if ($company->trashed())
    <form
        action="{{ route('companies.restore', $company->id) }}"
        method="POST"
        class="d-inline">
        @csrf
        <button class="btn btn-sm btn-outline-success">Restore</button>
    </form>

    <form
        action="{{ route('companies.force-delete', $company->id) }}"
        method="POST"
        class="d-inline"
        onsubmit="return confirm('Permanently delete this company?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-sm btn-outline-danger">Delete Permanently</button>
    </form>
@endif
