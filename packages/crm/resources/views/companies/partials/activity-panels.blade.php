<div class="d-grid gap-4">
    <div>
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-dark text-white">
                <strong>Notes</strong>
            </div>
            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('crm.companies.notes.store', $company) }}"
                    class="crm-company-modal-form mb-4">
                    @csrf
                    <label for="company-note-input-{{ $company->id }}" class="form-label">Add
                        Note</label>
                    <input
                        type="hidden"
                        id="company-note-input-{{ $company->id }}"
                        name="content"
                        value="">
                    <div
                        id="company-note-editor-{{ $company->id }}"
                        class="crm-quill-field"
                        data-editor-id="company-note-editor-{{ $company->id }}"
                        data-input-target="#company-note-input-{{ $company->id }}">
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-teal">Save Note</button>
                    </div>
                </form>

                <div class="crm-notes-list">
                    @forelse($company->notes as $note)
                        @include('crm::companies.partials.note-item', [
                            'note' => $note,
                        ])
                    @empty
                        <p class="text-muted mb-0 crm-notes-empty">No notes yet for this company.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-dark text-white">
                <strong>Phone Calls</strong>
            </div>
            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('crm.companies.phone-calls.store', $company) }}"
                    class="crm-company-modal-form mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="company-call-date-{{ $company->id }}" class="form-label">Call
                            Date</label>
                        <input
                            type="datetime-local"
                            id="company-call-date-{{ $company->id }}"
                            name="called_at"
                            class="form-control crm-local-datetime-default"
                            value="{{ now()->format('Y-m-d\\TH:i') }}"
                            required>
                    </div>
                    <label for="company-call-notes-input-{{ $company->id }}"
                        class="form-label">Call Notes</label>
                    <input
                        type="hidden"
                        id="company-call-notes-input-{{ $company->id }}"
                        name="notes"
                        value="">
                    <div
                        id="company-call-notes-editor-{{ $company->id }}"
                        class="crm-quill-field"
                        data-editor-id="company-call-notes-editor-{{ $company->id }}"
                        data-input-target="#company-call-notes-input-{{ $company->id }}">
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary btn-teal">Log Phone
                            Call</button>
                    </div>
                </form>

                <div class="crm-phone-calls-list">
                    @forelse($company->phoneCalls as $phoneCall)
                        @include('crm::companies.partials.phone-call-item', [
                            'phoneCall' => $phoneCall,
                        ])
                    @empty
                        <p class="text-muted mb-0 crm-phone-calls-empty">No phone calls logged for
                            this company.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
