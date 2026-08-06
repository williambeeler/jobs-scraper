@extends('layouts.app')

@section('title', $certificate->title . ' | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">Certificate</p>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <h1 class="display-6 fw-bold mb-0">{{ $certificate->title }}</h1>
                    <span class="badge {{ $certificate->is_completed ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $certificate->is_completed ? 'Completed' : 'In progress' }}
                    </span>
                </div>
                <p class="text-secondary mb-0">Agenda: {{ $certificate->agenda->title }}</p>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-primary" href="{{ route('learning.certificates.edit', $certificate) }}">Edit</a>
                <form method="POST" action="{{ route('learning.certificates.destroy', $certificate) }}"
                    onsubmit="return confirm('Delete this certificate?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            </div>
        </header>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <section class="card shadow-sm mb-4">
                    <div class="card-header bg-white p-4 pb-0 border-0"><h2 class="h5 mb-0">Description</h2></div>
                    <div class="card-body p-4">
                        <p class="mb-0 {{ $certificate->description ? '' : 'text-body-secondary' }}" style="white-space: pre-wrap;">{{ $certificate->description ?: 'No description yet.' }}</p>
                    </div>
                </section>
                <section class="card shadow-sm">
                    <div class="card-header bg-white p-4 pb-0 border-0"><h2 class="h5 mb-0">Study tips</h2></div>
                    <div class="card-body p-4">
                        <p class="mb-0 {{ $certificate->study_tips ? '' : 'text-body-secondary' }}" style="white-space: pre-wrap;">{{ $certificate->study_tips ?: 'No study tips yet.' }}</p>
                    </div>
                </section>
            </div>
            <aside class="col-12 col-lg-4">
                <section class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Certificate details</h2>
                        <dl class="mb-0">
                            <dt class="small text-body-secondary">Agenda</dt>
                            <dd>{{ $certificate->agenda->title }}</dd>
                            <dt class="small text-body-secondary">Completion date</dt>
                            <dd>{{ $certificate->completion_date?->format('F j, Y') ?? 'Not set' }}</dd>
                            <dt class="small text-body-secondary">Certificate URL</dt>
                            <dd class="mb-0 text-break">
                                @if ($certificate->url)
                                    <a href="{{ $certificate->url }}" target="_blank" rel="noopener noreferrer">Open certificate</a>
                                @else
                                    Not set
                                @endif
                            </dd>
                        </dl>
                    </div>
                </section>
            </aside>
        </div>

        @include('learning.modules._manager', [
            'moduleable' => $certificate,
            'moduleStoreUrl' => route('learning.certificates.modules.store', $certificate),
        ])

        <div class="mt-4">
            <a class="btn btn-link px-0" href="{{ route('learning.certificates.index') }}">← Back to certificates</a>
        </div>
    </main>
@endsection
