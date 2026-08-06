@extends('layouts.app')

@section('title', 'Bible Search | ' . config('app.name'))

@push('styles')
    @vite('resources/css/bible.css')
@endpush

@section('content')
    <main class="container py-5">
        <div class="bible-page">
            <header class="bible-search-header">
                <div class="bible-hero-kicker">Bible Search</div>
                <h1>Search Scripture</h1>
                <p>Search for words or phrases, or enter a reference such as <strong>John 3</strong>, <strong>John 3:16</strong>, or <strong>John 3:16-4:2</strong>.</p>

                <form method="GET" action="{{ route('bible.search') }}" class="bible-search-form">
                    <label class="visually-hidden" for="bible-search-query">Search Scripture</label>
                    <input
                        id="bible-search-query"
                        class="form-control form-control-lg"
                        type="search"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Search words, verses, or chapters…"
                        autofocus
                    >
                    <label class="visually-hidden" for="bible-search-translation">Bible translation</label>
                    <select id="bible-search-translation" class="form-select form-select-lg" name="translation">
                        @foreach ($translations as $option)
                            <option value="{{ $option->value }}" @selected($translation === $option)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary btn-lg" type="submit">Search</button>
                </form>
            </header>

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
                        <div>{{ $results->links() }}</div>
                    @endif
                </section>
            @endif
        </div>
    </main>
@endsection
