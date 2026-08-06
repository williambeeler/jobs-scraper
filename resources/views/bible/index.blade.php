@extends('layouts.app')

@section('title', 'Bible | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@section('content')
    <main class="container py-5">
        <div class="bible-page">
            @include('bible._worksheet-hero')

            @include('bible._search-form', [
                'searchInputId' => 'bible-dashboard-search-query',
                'translationInputId' => 'bible-dashboard-search-translation',
            ])

            <section class="bible-dashboard-card">
                <div>
                    <div class="bible-hero-kicker">Daily Reading</div>
                    <h2>Continue a Bible reading plan</h2>
                    <p>Choose one of the imported plans, read today’s passages in NET, CSB, or ESV, and sync completed chapters to your worksheet.</p>
                </div>
                <a class="btn btn-primary" href="{{ route('bible.plans.index') }}">Open Reading Plans</a>
            </section>
        </div>
    </main>
@endsection
