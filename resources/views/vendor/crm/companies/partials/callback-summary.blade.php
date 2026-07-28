@if($company->needs_callback)
    <span class="badge text-bg-warning">Call back{{ $company->callback_at ? ' on ' . $company->callback_at->format('M d, Y g:i A') : '' }}</span>
@else
    <span class="text-muted">No callback scheduled.</span>
@endif
