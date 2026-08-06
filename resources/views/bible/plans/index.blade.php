@extends('layouts.app')

@section('title', 'Bible Reading Plans | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@section('content')
    <main class="container py-5">
        <div class="bible-page">
            <header class="bible-reader-header">
                <div>
                    <div class="bible-hero-kicker">Bible Reading Plans</div>
                    <h1>Today’s Reading</h1>
                    <p>{{ $today->format('l, F j, Y') }}</p>
                </div>

                <form method="GET" action="{{ route('bible.plans.index') }}" class="bible-plan-filters">
                    <label>
                        <span>Plan</span>
                        <select name="plan" class="form-select" onchange="this.form.submit()">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->slug }}" @selected($selectedPlan?->is($plan))>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if (($selectedPlan?->duration_years ?? 1) > 1)
                        <label>
                            <span>Plan year</span>
                            <select name="cycle" class="form-select" onchange="this.form.submit()">
                                @for ($year = 1; $year <= $selectedPlan->duration_years; $year++)
                                    <option value="{{ $year }}" @selected($cycleYear === $year)>Year {{ $year }}</option>
                                @endfor
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="cycle" value="1">
                    @endif

                    <label>
                        <span>Translation</span>
                        <select name="translation" class="form-select" onchange="this.form.submit()">
                            @foreach ($translations as $option)
                                <option value="{{ $option->value }}" @selected($translation === $option)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
            </header>

            @if (session('status'))
                <div class="alert alert-success mb-0">{{ session('status') }}</div>
            @endif

            @if (! $selectedPlan)
                <section class="bible-reader-empty">Run the Bible reading-plan seeder to load the attached schedules.</section>
            @elseif (! $day)
                <section class="bible-reader-empty">
                    <h2>Catch-up day</h2>
                    <p class="mb-0">This plan has no scheduled reading for today. February 29 is intentionally left open in leap years.</p>
                </section>
            @else
                <section class="bible-reader-summary">
                    <div>
                        <div class="bible-progress-label">{{ $selectedPlan->name }}@if ($selectedPlan->duration_years > 1) · Year {{ $cycleYear }}@endif</div>
                        <h2>{{ $day->is_catch_up ? 'Catch-up day' : ($day->passages->count() . ' prescribed reading' . ($day->passages->count() === 1 ? '' : 's')) }}</h2>
                        <p>{{ $selectedPlan->description }}</p>
                    </div>
                    @if ($completed)
                        <span class="badge text-bg-success align-self-start">Completed</span>
                    @endif
                </section>

                @forelse ($readings as $reading)
                    <article class="bible-passage">
                        <header>
                            <span>{{ $translation->label() }}</span>
                            <h2>{{ $reading['passage']->reference }}</h2>
                        </header>
                        @foreach ($reading['chapters'] as $chapter => $verses)
                            <section class="bible-passage-chapter">
                                <h3>{{ $reading['book']['name'] }} {{ $chapter }}</h3>
                                <p>
                                    @foreach ($verses as $verse)
                                        <sup>{{ $verse->verse }}</sup>{{ $verse->text }}{{ ! $loop->last ? ' ' : '' }}
                                    @endforeach
                                </p>
                            </section>
                        @endforeach
                    </article>
                @empty
                    <section class="bible-reader-empty">
                        <h2>Nothing assigned beyond catch-up</h2>
                        <p class="mb-0">Use today to catch up or revisit a previous reading.</p>
                    </section>
                @endforelse

                @if ($day->passages->isNotEmpty() && ! $completed)
                    <form method="POST" action="{{ route('bible.plans.complete', $day) }}" class="text-center">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $selectedPlan->slug }}">
                        <input type="hidden" name="cycle" value="{{ $cycleYear }}">
                        <input type="hidden" name="translation" value="{{ $translation->value }}">
                        <button class="btn btn-lg btn-primary" type="submit">I’ve read today’s passages</button>
                        <p class="text-body-secondary small mt-2 mb-0">This checks off every chapter touched by today’s reading in the {{ $today->year }} worksheet.</p>
                    </form>
                @endif
            @endif
        </div>
    </main>
@endsection
