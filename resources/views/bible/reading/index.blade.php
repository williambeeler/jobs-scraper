@extends('layouts.app')

@section('title', 'Bible Reading | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@push('scripts')
    @vite('resources/js/bible.js')
@endpush

@section('content')
    <main class="container py-5">
        <div
            class="bible-page"
            data-bible-reading-page
            data-update-url-template="{{ route('bible.reading.chapters.update', ['year' => '__YEAR__', 'book' => '__BOOK__', 'chapter' => '__CHAPTER__']) }}"
        >
            @include('bible._worksheet-hero')

            @foreach ($booksByTestament as $testament => $books)
                <section class="bible-section">
                    <div class="bible-section-header">
                        <h2>{{ $testament }}</h2>
                        <span>{{ count($books) }} books</span>
                    </div>

                    <div class="bible-books-grid">
                        @foreach ($books as $book)
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

                                <div
                                    class="bible-chapter-grid"
                                    role="group"
                                    aria-label="{{ $book['name'] }} chapters"
                                >
                                    @foreach (range(1, $book['chapters']) as $chapter)
                                        @php
                                            $isChecked = in_array(
                                                $chapter,
                                                $completedBookChapters,
                                                true,
                                            );
                                        @endphp

                                        <label class="bible-chapter-box"
                                            data-bible-chapter-key="{{ $book['slug'] }}:{{ $chapter }}"
                                        >
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
    </main>
@endsection
