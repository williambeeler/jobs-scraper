@extends('layouts.app')

@section('title', 'Bible | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@section('content')
    <main class="container py-5">
        <div class="bible-page">
            @include('bible._worksheet-hero')
        </div>
    </main>
@endsection
