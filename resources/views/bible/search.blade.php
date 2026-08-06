@extends('layouts.app')

@section('title', 'Bible Search | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@section('content')
    <main class="container py-5">
        <div class="bible-page">
            @include('bible._search-form', [
                'searchInputId' => 'bible-search-query',
                'translationInputId' => 'bible-search-translation',
                'autofocusSearch' => true,
            ])

            @if ($results)
                <section class="bible-search-results" aria-live="polite">
                    <header class="bible-section-header">
                        <div>
                            <span>{{ $translation->label() }} · {{ $searchMode === 'reference' ? 'Reference' : 'Text' }} search</span>
                            <h2>{{ number_format($results->total()) }} {{ Str::plural('verse', $results->total()) }} found for “{{ $query }}”</h2>
                        </div>
                    </header>

                    @forelse ($results->groupBy(fn ($verse) => $verse->book_id.'-'.$verse->chapter) as $chapterVerses)
                        @php($firstVerse = $chapterVerses->first())
                        <article class="bible-search-chapter">
                            <h3>{{ $firstVerse->book }} {{ $firstVerse->chapter }}</h3>
                            <div class="bible-search-verses">
                                @foreach ($chapterVerses as $verse)
                                    <p id="verse-{{ $verse->book_id }}-{{ $verse->chapter }}-{{ $verse->verse }}">
                                        <a href="#verse-{{ $verse->book_id }}-{{ $verse->chapter }}-{{ $verse->verse }}" aria-label="{{ $verse->book }} {{ $verse->chapter }}:{{ $verse->verse }}">{{ $verse->verse }}</a>
                                        <span>{{ $verse->text }}</span>
                                    </p>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="bible-reader-empty">
                            <h2>No verses found</h2>
                            <p class="mb-0">Try a shorter phrase, another spelling, or a reference such as John 3:16.</p>
                        </div>
                    @endforelse

                    @if ($results->hasPages())
                        <div>{{ $results->links('pagination::bootstrap-5') }}</div>
                    @endif
                </section>
            @endif
        </div>
    </main>
@endsection
