@extends('layouts.app')

@section('title', 'Certificates | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
                <h1 class="display-5 fw-bold mb-2">Certificates</h1>
                <p class="text-secondary mb-0">Track certifications and study plans connected to your agendas.</p>
            </div>
            <a class="btn btn-primary flex-shrink-0" href="{{ route('learning.certificates.create') }}">Add certificate</a>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <form class="card card-body shadow-sm mb-4" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md">
                    <label class="form-label fw-semibold" for="agenda">Agenda</label>
                    <select class="form-select" id="agenda" name="agenda">
                        <option value="">All agendas</option>
                        @foreach ($agendas as $agenda)
                            <option value="{{ $agenda->id }}" @selected($agendaId === $agenda->id)>{{ $agenda->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button class="btn btn-outline-primary" type="submit">Filter</button>
                    @if ($agendaId)
                        <a class="btn btn-outline-secondary" href="{{ route('learning.certificates.index') }}">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($certificates->isEmpty())
            <div class="alert alert-secondary" role="status">
                {{ $agendaId ? 'This agenda has no certificates yet.' : 'No certificates yet. Add the first one to get started.' }}
            </div>
        @else
            <div class="list-group shadow-sm">
                @foreach ($certificates as $certificate)
                    <article class="list-group-item p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <h2 class="h5 mb-0">
                                        <a class="link-dark link-underline-opacity-0 link-underline-opacity-100-hover"
                                            href="{{ route('learning.certificates.show', $certificate) }}">{{ $certificate->title }}</a>
                                    </h2>
                                    <span class="badge {{ $certificate->is_completed ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $certificate->is_completed ? 'Completed' : 'In progress' }}
                                    </span>
                                </div>
                                <p class="text-body-secondary mb-2">
                                    Agenda: <a href="{{ route('learning.certificates.index', ['agenda' => $certificate->agenda->id]) }}">{{ $certificate->agenda->title }}</a>
                                </p>
                                @if ($certificate->description)
                                    <p class="mb-0">{{ str($certificate->description)->limit(180) }}</p>
                                @endif
                            </div>
                            <div class="d-flex flex-column align-items-start align-items-md-end gap-2 flex-shrink-0">
                                @if ($certificate->completion_date)
                                    <small class="text-body-secondary">Completion: {{ $certificate->completion_date->format('M j, Y') }}</small>
                                @endif
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('learning.certificates.edit', $certificate) }}">Edit</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            @if ($certificates->hasPages())
                <div class="mt-4">{{ $certificates->links('pagination::bootstrap-5') }}</div>
            @endif
        @endif
    </main>
@endsection
