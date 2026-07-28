@extends('bible::layouts.dashboard')

@php
    $completionPercent = $totalChaptersCount > 0 ? (int) round(($completedChaptersCount / $totalChaptersCount) * 100) : 0;
@endphp

@push('styles')
    @vite('packages/dailydash/bible/resources/css/bible.css')
@endpush

@push('scripts')
    @vite('packages/dailydash/bible/resources/js/bible.js')
@endpush

@section('content')
    <div
        class="bible-page"
        data-bible-reading-page
        data-update-url-template="{{ route('bible.reading.chapters.update', ['year' => '__YEAR__', 'book' => '__BOOK__', 'chapter' => '__CHAPTER__']) }}"
    >
        <section class="bible-hero">
            <div>
                <div class="bible-hero-kicker">Daily Dash</div>
                <div class="bible-hero-year-wrap">
                    <a
                        class="bible-year-bumper"
                        href="{{ route('bible.reading.index', ['year' => $displayYear - 1]) }}"
                        aria-label="Show {{ $displayYear - 1 }}"
                    >
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>

                    <div>
                        <h1 class="bible-hero-year">{{ $displayYear }}</h1>
                        <p class="bible-hero-subtitle mb-0">
                            {{ $completedChaptersCount }} of {{ $totalChaptersCount }} chapters completed
                        </p>
                    </div>

                    <a
                        class="bible-year-bumper"
                        href="{{ route('bible.reading.index', ['year' => $displayYear + 1]) }}"
                        aria-label="Show {{ $displayYear + 1 }}"
                    >
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </div>
            </div>

            <div class="bible-progress-panel">
                <div class="bible-progress-label">Year progress</div>
                <div class="bible-progress-value" data-bible-progress>{{ $completionPercent }}%</div>
                <div class="bible-progress-bar" aria-hidden="true">
                    <span style="width: {{ $completionPercent }}%"></span>
                </div>
                <div class="bible-progress-footnote" data-bible-feedback>
                    {{ $displayYear === $currentYear ? 'Current year loaded by default.' : 'Use the book icon to jump back to the current year.' }}
                </div>
            </div>
        </section>

        @foreach($booksByTestament as $testament => $books)
            <section class="bible-section">
                <div class="bible-section-header">
                    <h2>{{ $testament }}</h2>
                    <span>{{ count($books) }} books</span>
                </div>

                <div class="bible-books-grid">
                    @foreach($books as $book)
                        @php
                            $completedBookChapters = $readChapters[$book['slug']] ?? [];
                        @endphp

                        <article class="bible-book-card">
                            <div class="bible-book-header">
                                <h3>{{ $book['name'] }}</h3>
                                <span data-bible-book-progress="{{ $book['slug'] }}">
                                    {{ count($completedBookChapters) }}/{{ $book['chapters'] }}
                                </span>
                            </div>

                            <div class="bible-chapter-grid" role="group" aria-label="{{ $book['name'] }} chapters">
                                @foreach(range(1, $book['chapters']) as $chapter)
                                    @php
                                        $isChecked = in_array($chapter, $completedBookChapters, true);
                                    @endphp

                                    <label class="bible-chapter-box" data-bible-chapter-key="{{ $book['slug'] }}:{{ $chapter }}">
                                        <input
                                            class="bible-chapter-checkbox"
                                            type="checkbox"
                                            data-book="{{ $book['slug'] }}"
                                            data-chapter="{{ $chapter }}"
                                            data-year="{{ $displayYear }}"
                                            {{ $isChecked ? 'checked' : '' }}
                                        >
                                        <span>{{ $chapter }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection
