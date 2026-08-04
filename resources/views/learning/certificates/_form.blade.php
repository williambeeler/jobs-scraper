<div class="card shadow-sm">
    <div class="card-body p-4 p-md-5">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold" for="learning_topic_id">Agenda</label>
                <select class="form-select @error('learning_topic_id') is-invalid @enderror"
                    id="learning_topic_id" name="learning_topic_id" required>
                    <option value="">Select an agenda</option>
                    @foreach ($agendas as $agenda)
                        <option value="{{ $agenda->id }}" @selected((string) old('learning_topic_id', $certificate->learning_topic_id) === (string) $agenda->id)>
                            {{ $agenda->title }}</option>
                    @endforeach
                </select>
                @error('learning_topic_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="title">Certificate title</label>
                <input class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                    value="{{ old('title', $certificate->title) }}" required autofocus>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="url">Certificate URL</label>
                <input class="form-control @error('url') is-invalid @enderror" id="url" name="url" type="url"
                    value="{{ old('url', $certificate->url) }}" placeholder="https://example.com/certificate">
                @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                    name="description" rows="7">{{ old('description', $certificate->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="study_tips">Study tips</label>
                <textarea class="form-control @error('study_tips') is-invalid @enderror" id="study_tips"
                    name="study_tips" rows="7">{{ old('study_tips', $certificate->study_tips) }}</textarea>
                @error('study_tips')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold" for="completion_date">Completion date</label>
                <input class="form-control @error('completion_date') is-invalid @enderror" id="completion_date"
                    name="completion_date" type="date"
                    value="{{ old('completion_date', $certificate->completion_date?->format('Y-m-d')) }}">
                @error('completion_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end pb-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_completed"
                        name="is_completed" value="1" @checked(old('is_completed', $certificate->is_completed))>
                    <label class="form-check-label fw-semibold" for="is_completed">Certificate completed</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white p-4 d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
    </div>
</div>
