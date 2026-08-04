@extends('layouts.app')
@section('title', $careerPath->title . ' | ' . config('app.name'))
@section('content')
    <main class="container py-5" style="max-width: 960px;">
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">Career path</p>
                <h1 class="display-5 fw-bold mb-2">{{ $careerPath->title }}</h1>
                @if ($careerPath->description)
                    <div class="career-path-description lead text-secondary">{!! $careerPath->description !!}</div>
                @endif
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('learning.career-paths.edit', $careerPath) }}">Edit</a>
                <form method="POST" action="{{ route('learning.career-paths.destroy', $careerPath) }}" onsubmit="return confirm('Delete this career path? The agendas will not be deleted.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger">Delete</button>
                </form>
            </div>
        </header>

        @if ($careerPath->files->isNotEmpty())
            <section class="mb-4" aria-labelledby="career-path-files-heading">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h4 mb-0" id="career-path-files-heading">Files</h2>
                    <span class="text-body-secondary small">{{ trans_choice(':count file|:count files', $careerPath->files->count(), ['count' => $careerPath->files->count()]) }}</span>
                </div>
                <div class="row g-3">
                    @foreach ($careerPath->files as $file)
                        <div class="col-12 col-md-6 col-xl-4">
                            <article class="card h-100 shadow-sm overflow-hidden">
                                @if ($file->isImage())
                                    <a href="{{ route('learning.career-paths.files.show', [$careerPath, $file]) }}" target="_blank">
                                        <img class="card-img-top object-fit-cover" style="height: 11rem;" src="{{ route('learning.career-paths.files.show', [$careerPath, $file]) }}" alt="{{ $file->original_name }}">
                                    </a>
                                @endif
                                <div class="card-body p-3">
                                    <h3 class="h6 text-break mb-1">{{ $file->original_name }}</h3>
                                    <p class="small text-body-secondary mb-0">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }} · {{ $file->formattedSize() }}</p>
                                </div>
                                <div class="card-footer bg-white d-flex justify-content-between gap-2 p-3">
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('learning.career-paths.files.show', [$careerPath, $file]) }}" target="_blank">Open</a>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('learning.career-paths.files.download', [$careerPath, $file]) }}">Download</a>
                                    </div>
                                    <form method="POST" action="{{ route('learning.career-paths.files.destroy', [$careerPath, $file]) }}" onsubmit="return confirm('Delete this file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $file->original_name }}">Delete</button>
                                    </form>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="card shadow-sm">
            <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                <h2 class="h4 mb-0">Topic agendas</h2>
                <a class="btn btn-sm btn-primary" href="{{ route('learning.agenda') }}">Manage agendas</a>
            </div>
            @if ($careerPath->agendas->isEmpty())
                <div class="card-body p-4 text-secondary">No agendas are assigned to this career path yet.</div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($careerPath->agendas as $agenda)
                        <a class="list-group-item list-group-item-action p-4 d-flex justify-content-between align-items-center gap-3"
                            href="{{ route('learning.agenda.topics.edit', $agenda) }}">
                            <span class="fw-semibold">{{ $agenda->title }}</span>
                            <span class="d-flex gap-2">
                                <span class="badge text-bg-light">{{ \App\Models\LearningTopic::PRIORITIES[$agenda->priority] }}</span>
                                <span class="badge text-bg-{{ $agenda->status === 'completed' ? 'success' : ($agenda->status === 'in_progress' ? 'primary' : 'secondary') }}">{{ \App\Models\LearningTopic::STATUSES[$agenda->status] }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </main>
@endsection
