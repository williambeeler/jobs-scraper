@extends('layouts.app')

@section('title', 'News | ' . config('app.name'))

@push('styles')
    @vite('resources/css/news.css')
@endpush

@section('content')
    <main class="container py-5">
        <header
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4"
        >
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">RSS reader</p>
                <h1 class="display-6 fw-bold mb-2">Latest news</h1>
                <p class="text-secondary mb-0">Articles are organized by their published date.</p>
            </div>
            <form method="GET" action="{{ route('news') }}">
                <label class="form-label fw-semibold" for="news-feed">Feed</label>
                <select
                    class="form-select"
                    id="news-feed"
                    name="feed"
                    onchange="this.form.submit()"
                >
                    <option value="">All feeds</option>
                    @foreach ($feeds as $feed)
                        <option value="{{ $feed->id }}" @selected((string) $selectedFeedId === (string) $feed->id)>
                            {{ $feed->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </header>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif

        @forelse ($articles->groupBy(fn ($article) => $article->published_at?->toDateString() ?? 'unknown') as $date => $datedArticles)
            <section class="mb-5">
                <h2 class="h5 fw-bold border-bottom pb-2 mb-3">
                    {{ $date === 'unknown' ? 'Date unavailable' : $datedArticles->first()->published_at->format('l, F j, Y') }}
                </h2>
                <div class="vstack gap-3">
                    @foreach ($datedArticles as $article)
                        @include('news._article')
                    @endforeach
                </div>
            </section>
        @empty
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <h2 class="h5">No articles yet</h2>
                    <p class="text-secondary">Add an RSS or Atom feed to start collecting news.</p>
                    <a class="btn btn-primary" href="{{ route('news.feeds.index') }}">Add a feed</a>
                </div>
            </div>
        @endforelse

        @if ($articles->hasPages())
            <div class="mt-4">{{ $articles->links('pagination::bootstrap-5') }}</div>
        @endif
    </main>
@endsection
