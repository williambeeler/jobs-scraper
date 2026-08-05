@extends('layouts.app')

@section('title', 'News Feeds | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">RSS reader</p>
            <h1 class="display-6 fw-bold mb-2">Feeds</h1>
            <p class="text-secondary mb-0">Add RSS, XML, or Atom feed URLs and refresh them whenever you
                like.</p>
        </header>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-warning">{{ session('error') }}</div>
        @endif

        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Add a feed</h2>
                <form method="POST" action="{{ route('news.feeds.store') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="feed-name">Name <span
                                    class="text-secondary fw-normal"
                                >(optional)</span></label>
                            <input
                                class="form-control @error('name') is-invalid @enderror"
                                id="feed-name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Site or publication"
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="feed-url">Feed URL</label>
                            <input
                                class="form-control @error('url') is-invalid @enderror"
                                id="feed-url"
                                name="url"
                                type="url"
                                value="{{ old('url') }}"
                                placeholder="https://example.com/feed.xml"
                                required
                            >
                            @error('url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2 d-grid">
                            <button class="btn btn-primary" type="submit">Add feed</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section aria-labelledby="saved-feeds-heading">
            <h2 class="h5 mb-3" id="saved-feeds-heading">Saved feeds</h2>
            <div class="list-group shadow-sm">
                @forelse ($feeds as $feed)
                    <article class="list-group-item p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                            <div>
                                <h3 class="h5 mb-1">{{ $feed->name }}</h3>
                                <a
                                    class="small text-break"
                                    href="{{ $feed->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >{{ $feed->url }}</a>
                                <div class="small text-secondary mt-2">
                                    {{ trans_choice(':count article|:count articles', $feed->articles_count, ['count' => $feed->articles_count]) }}
                                    <span aria-hidden="true">&middot;</span>
                                    {{ $feed->last_fetched_at ? 'Last refreshed ' . $feed->last_fetched_at->diffForHumans() : 'Not refreshed yet' }}
                                </div>
                                @if ($feed->last_error)
                                    <div class="text-danger small mt-2">{{ $feed->last_error }}</div>
                                @endif
                            </div>
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('news', ['feed' => $feed->id]) }}"
                                >View articles</a>
                                <form method="POST" action="{{ route('news.feeds.refresh', $feed) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary"
                                        type="submit">Refresh</button>
                                </form>
                                <form
                                    method="POST"
                                    action="{{ route('news.feeds.destroy', $feed) }}"
                                    onsubmit="return confirm('Remove this feed and all of its articles?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                        type="submit">Remove</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="list-group-item p-4 text-secondary">No feeds have been added.</div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
