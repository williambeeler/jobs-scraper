@extends('layouts.app')
@section('title', 'Edit ' . $careerPath->title . ' | ' . config('app.name'))
@section('content')
    <main class="container py-5" style="max-width: 820px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Career path</p>
            <h1 class="display-6 fw-bold mb-2">Edit {{ $careerPath->title }}</h1>
        </header>
        <form method="POST" action="{{ route('learning.career-paths.update', $careerPath) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('learning.career-paths._form', ['submitLabel' => 'Save changes', 'cancelUrl' => route('learning.career-paths.show', $careerPath)])
        </form>
    </main>
@endsection
