@extends('layouts.app')

@section('title', 'Add Certificate | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 860px;">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
            <h1 class="display-6 fw-bold mb-2">Add certificate</h1>
            <p class="text-secondary mb-0">Connect a certificate and its study plan to an agenda.</p>
        </header>
        <form method="POST" action="{{ route('learning.certificates.store') }}">
            @csrf
            @include('learning.certificates._form', [
                'submitLabel' => 'Add certificate',
                'cancelUrl' => route('learning.certificates.index'),
            ])
        </form>
    </main>
@endsection
