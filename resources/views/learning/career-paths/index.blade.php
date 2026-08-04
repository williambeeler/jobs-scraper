@extends('layouts.app')
@section('title', 'Career Paths | ' . config('app.name'))
@section('content')
    <main class="container py-5">
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
                <h1 class="display-5 fw-bold mb-2">Career Paths</h1>
                <p class="text-secondary mb-0">Group topic agendas into the professional paths they support.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('learning.career-paths.create') }}">Add career path</a>
        </header>

        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

        @if ($careerPaths->isEmpty())
            <div class="alert alert-secondary">No career paths yet. Add one to start grouping your agenda.</div>
        @else
            <div class="row g-3">
                @foreach ($careerPaths as $careerPath)
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="card h-100 shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="h5"><a class="link-dark text-decoration-none"
                                        href="{{ route('learning.career-paths.show', $careerPath) }}">{{ $careerPath->title }}</a></h2>
                                <p class="text-body-secondary">{{ $careerPath->description ? \Illuminate\Support\Str::limit(strip_tags($careerPath->description), 180) : 'No description.' }}</p>
                                <span class="badge text-bg-secondary">{{ trans_choice(':count agenda|:count agendas', $careerPath->agendas_count, ['count' => $careerPath->agendas_count]) }}</span>
                            </div>
                            <div class="card-footer bg-white d-flex justify-content-end">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('learning.career-paths.edit', $careerPath) }}">Edit</a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </main>
@endsection
