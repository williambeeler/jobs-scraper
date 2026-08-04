<div class="card shadow-sm">
    <div class="card-body p-4 p-md-5">
        <div class="mb-3">
            <label class="form-label fw-semibold" for="title">Career path title</label>
            <input class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                value="{{ old('title', $careerPath->title) }}" required autofocus>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div>
            <label class="form-label fw-semibold" for="description">Description</label>
            <div class="note-editor border rounded @error('description') border-danger @enderror" data-note-editor>
                <div class="note-editor-toolbar d-flex flex-wrap gap-1 border-bottom bg-body-tertiary p-2"
                    role="toolbar" aria-label="Description formatting">
                    <button type="button" class="btn btn-sm btn-light" data-note-command="heading">Heading</button>
                    <button type="button" class="btn btn-sm btn-light fw-bold" data-note-command="bold" aria-label="Bold">B</button>
                    <button type="button" class="btn btn-sm btn-light fst-italic" data-note-command="italic" aria-label="Italic">I</button>
                    <button type="button" class="btn btn-sm btn-light text-decoration-underline" data-note-command="underline" aria-label="Underline">U</button>
                    <button type="button" class="btn btn-sm btn-warning" data-note-command="highlight">Highlight</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="bulletList">Bullets</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="orderedList">Numbers</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="blockquote">Quote</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="codeBlock">Code</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="link">Link</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="undo">Undo</button>
                    <button type="button" class="btn btn-sm btn-light" data-note-command="redo">Redo</button>
                </div>
                <div data-note-surface></div>
                <textarea class="visually-hidden" id="description" name="description" data-note-input>{{ old('description', $careerPath->description) }}</textarea>
            </div>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mt-4">
            <label class="form-label fw-semibold" for="career-path-files">Additional files</label>
            <label class="career-path-dropzone d-flex flex-column align-items-center justify-content-center text-center rounded p-4 @error('files') border-danger @enderror"
                for="career-path-files" data-file-dropzone>
                <span class="fs-2" aria-hidden="true">↥</span>
                <span class="fw-semibold">Drop files here or click to browse</span>
                <span class="small text-body-secondary mt-1">PDFs, Office documents, text files, and images · up to 20 MB each</span>
                <input class="visually-hidden" id="career-path-files" name="files[]" type="file" multiple
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv,.jpg,.jpeg,.png,.gif,.webp,.svg"
                    data-file-input>
            </label>
            <ul class="list-group list-group-flush mt-2 d-none" data-file-list aria-live="polite"></ul>
            @error('files')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('files.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @if ($careerPath->exists && $careerPath->files()->exists())
                <p class="form-text mb-0">Existing files remain attached. You can remove them from the Career Path page.</p>
            @endif
        </div>
    </div>
    <div class="card-footer bg-white p-4 d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancel</a>
        <button class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</div>

@push('scripts')
    @vite('resources/js/notes.ts')
    <script>
        document.querySelectorAll('[data-file-dropzone]').forEach((dropzone) => {
            const input = dropzone.querySelector('[data-file-input]');
            const list = dropzone.parentElement.querySelector('[data-file-list]');

            const renderFiles = () => {
                list.replaceChildren();
                list.classList.toggle('d-none', input.files.length === 0);

                Array.from(input.files).forEach((file) => {
                    const item = document.createElement('li');
                    item.className = 'list-group-item px-0 d-flex justify-content-between gap-3';
                    item.textContent = file.name;
                    const size = document.createElement('span');
                    size.className = 'text-body-secondary text-nowrap';
                    size.textContent = `${(file.size / 1048576).toFixed(1)} MB`;
                    item.append(size);
                    list.append(item);
                });
            };

            input.addEventListener('change', renderFiles);
            ['dragenter', 'dragover'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragging');
            }));
            ['dragleave', 'drop'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragging');
            }));
            dropzone.addEventListener('drop', (event) => {
                input.files = event.dataTransfer.files;
                renderFiles();
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .note-editor-surface {
            min-height: 14rem;
            padding: .75rem;
            outline: none;
        }

        .note-editor-surface> :last-child,
        .career-path-description> :last-child {
            margin-bottom: 0;
        }

        .note-editor-surface mark,
        .career-path-description mark {
            background-color: #fff176;
            border-radius: .125rem;
            padding: 0 .1em;
        }

        .career-path-dropzone {
            border: 2px dashed var(--bs-border-color);
            background: var(--bs-tertiary-bg);
            cursor: pointer;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .career-path-dropzone:hover,
        .career-path-dropzone.is-dragging {
            border-color: var(--bs-primary);
            background: var(--bs-primary-bg-subtle);
        }
    </style>
@endpush
