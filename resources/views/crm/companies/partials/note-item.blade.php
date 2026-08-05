<div class="border rounded p-3 mb-3 crm-note-item">
    <div class="crm-rich-text-output">{!! $note->content !!}</div>
    <small class="text-muted">
        {{ $note->created_at?->format('M d, Y g:i A') }}
    </small>
</div>
