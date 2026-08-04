@extends('layouts.app')

@section('title', 'Edit ' . $certificate->title . ' | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 860px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
            <h1 class="display-6 fw-bold mb-2">Edit certificate</h1>
            <p class="text-secondary mb-0">Update {{ $certificate->title }} and its progress.</p>
        </header>
        <form method="POST" action="{{ route('learning.certificates.update', $certificate) }}">
            @csrf
            @method('PUT')
            @include('learning.certificates._form', [
                'submitLabel' => 'Save changes',
                'cancelUrl' => route('learning.certificates.show', $certificate),
            ])
        </form>
    </main>
@endsection
