<section class="card shadow-sm mt-4">
    <div class="card-header bg-white p-4 pb-3">
        <div class="d-flex justify-content-between gap-3 align-items-center mb-3">
            <div><h2 class="h5 mb-1">Learning modules</h2>
                <p class="small text-body-secondary mb-0">{{ $moduleable->learningModules->where('is_completed', true)->count() }} of {{ $moduleable->learningModules->count() }} completed</p>
            </div>
            <span class="h5 mb-0">{{ $moduleable->progressPercentage() }}%</span>
        </div>
        <div class="progress" role="progressbar" aria-label="Learning progress"
            aria-valuenow="{{ $moduleable->progressPercentage() }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $moduleable->progressPercentage() }}%"></div>
        </div>
    </div>
    <div class="list-group list-group-flush">
        @forelse ($moduleable->learningModules as $module)
            <article class="list-group-item p-4 d-flex flex-column flex-md-row justify-content-between gap-3">
                <div><div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="h6 mb-0">{{ $module->title }}</h3>
                    <span class="badge {{ $module->is_completed ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $module->is_completed ? 'Completed' : 'Pending' }}</span>
                </div>
                @if ($module->description)<p class="text-body-secondary mb-1">{{ $module->description }}</p>@endif
                @if ($module->url)<a href="{{ $module->url }}" target="_blank" rel="noopener noreferrer">Open module</a>@endif
                @if ($module->completion_date)<small class="text-body-secondary d-block">Completed {{ $module->completion_date->format('M j, Y') }}</small>@endif
                </div>
                <div class="d-flex gap-2 align-self-md-start">
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('learning.modules.edit', $module) }}">Edit</a>
                    <form method="POST" action="{{ route('learning.modules.destroy', $module) }}" onsubmit="return confirm('Delete this learning module?')">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="list-group-item p-4 text-body-secondary">No learning modules yet.</div>
        @endforelse
    </div>
    <div class="card-body p-4 border-top">
        <h3 class="h6 mb-3">Add learning module</h3>
        <form method="POST" action="{{ $moduleStoreUrl }}">
            @csrf
            <div class="row g-3">
                <div class="col-12 col-md-6"><label class="form-label fw-semibold" for="module-title">Title</label><input class="form-control" id="module-title" name="title" required></div>
                <div class="col-12 col-md-6"><label class="form-label fw-semibold" for="module-url">Link</label><input class="form-control" id="module-url" name="url" type="url"></div>
                <div class="col-12"><label class="form-label fw-semibold" for="module-description">Description</label><textarea class="form-control" id="module-description" name="description" rows="3"></textarea></div>
                <div class="col-12 col-md-6"><label class="form-label fw-semibold" for="module-completion-date">Completion date</label><input class="form-control" id="module-completion-date" name="completion_date" type="date"></div>
                <div class="col-12 col-md-6 d-flex align-items-end pb-2"><div class="form-check form-switch"><input class="form-check-input" id="module-completed" name="is_completed" value="1" type="checkbox"><label class="form-check-label fw-semibold" for="module-completed">Completed</label></div></div>
            </div>
            <button class="btn btn-outline-primary mt-3" type="submit">Add module</button>
        </form>
    </div>
</section>
