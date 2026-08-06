<header class="bible-search-header">
    <div class="bible-hero-kicker">Bible Search</div>
    <h1>Search Scripture</h1>
    <p>Search for words or phrases, or enter a reference such as <strong>John 3</strong>, <strong>John 3:16</strong>, or <strong>John 3:16-4:2</strong>.</p>

    <form method="GET" action="{{ route('bible.search') }}" class="bible-search-form">
        <label class="visually-hidden" for="{{ $searchInputId }}">Search Scripture</label>
        <input
            id="{{ $searchInputId }}"
            class="form-control form-control-lg"
            type="search"
            name="q"
            value="{{ $query }}"
            placeholder="Search words, verses, or chapters…"
            @if ($autofocusSearch ?? false) autofocus @endif
        >
        <label class="visually-hidden" for="{{ $translationInputId }}">Bible translation</label>
        <select id="{{ $translationInputId }}" class="form-select form-select-lg" name="translation">
            @foreach ($translations as $option)
                <option value="{{ $option->value }}" @selected($translation === $option)>{{ $option->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-lg" type="submit">Search</button>
    </form>
</header>
