<div class="border rounded p-3 mb-3 crm-phone-call-item">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
        <strong>{{ $phoneCall->called_at?->format('M d, Y g:i A') ?? 'No date recorded' }}</strong>
    </div>
    <div class="crm-rich-text-output">{!! $phoneCall->notes !!}</div>
</div>
