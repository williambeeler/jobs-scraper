@extends('layouts.app')
@section('title', 'Add Career Path | ' . config('app.name'))
@section('content')
    <main class="container py-5" style="max-width: 820px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
            <h1 class="display-6 fw-bold mb-2">Add Career Path</h1>
            <p class="text-secondary mb-0">Create a taxonomy for grouping related topic agendas.</p>
        </header>
        <form method="POST" action="{{ route('learning.career-paths.store') }}" enctype="multipart/form-data">
            @csrf
            @include('learning.career-paths._form', ['submitLabel' => 'Add career path', 'cancelUrl' => route('learning.career-paths.index')])
        </form>
    </main>
@endsection
