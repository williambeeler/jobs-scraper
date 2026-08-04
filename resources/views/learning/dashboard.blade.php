@extends('layouts.app')

@section('title', 'Learning Center | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
            <h1 class="display-5 fw-bold mb-2">Dashboard</h1>
            <p class="text-secondary mb-0">Your learning activity and resources will come together here.</p>
        </header>

        <section class="row g-3" aria-label="Learning tools">
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card card-body h-100 shadow-sm text-decoration-none" href="{{ route('learning.agenda') }}">
                    <h2 class="h5 text-body">Topic agenda</h2>
                    <p class="text-secondary mb-0">Plan, prioritize, and track the topics you want to learn.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card card-body h-100 shadow-sm text-decoration-none"
                    href="{{ route('learning.decks.index') }}">
                    <h2 class="h5 text-body">Flash Cards</h2>
                    <p class="text-secondary mb-0">Organize questions and answers into decks by difficulty.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card card-body h-100 shadow-sm text-decoration-none"
                    href="{{ route('learning.projects.index') }}">
                    <h2 class="h5 text-body">Portfolio Projects</h2>
                    <p class="text-secondary mb-0">Build practical work and track its learning modules by agenda.</p>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card card-body h-100 shadow-sm text-decoration-none"
                    href="{{ route('learning.certificates.index') }}">
                    <h2 class="h5 text-body">Certificates</h2>
                    <p class="text-secondary mb-0">Track certifications, study notes, and completion dates by agenda.</p>
                </a>
            </div>
        </section>
    </main>
@endsection
