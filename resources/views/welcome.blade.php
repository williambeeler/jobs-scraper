@extends('layouts.app')

@section('title', 'Dashboard | ' . config('app.name'))

@push('styles')
    @vite('resources/css/news.css')
@endpush

@section('content')
    <main class="container py-5">
        <header class="text-center py-4 mb-4">
            <p class="mb-2 text-uppercase text-primary fw-semibold small">Dashboard</p>
            <h1 class="display-5 fw-bold mb-0">{{ $greeting }}</h1>
        </header>

        <section aria-labelledby="recent-jobs-heading">
            <h2 class="visually-hidden" id="recent-jobs-heading">Recent job listings</h2>
            <div class="list-group shadow-sm">
                @foreach ($dates as $item)
                    <a class="list-group-item list-group-item-action p-4 d-flex justify-content-between align-items-center gap-3"
                        href="{{ $item['url'] }}">
                        <div>
                            <p class="h5 mb-1">{{ $item['date']->format('l, F j') }}</p>
                            <small class="text-body-secondary">{{ $item['date']->isToday() ? 'Today' : $item['date']->format('Y') }}</small>
                        </div>
                        <span class="badge rounded-pill text-bg-primary fs-6">
                            {{ trans_choice(':count job|:count jobs', $item['count'], ['count' => $item['count']]) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Bible Stuff Here -->
        <!-- Newest Added Jobs...  -->
        <section class="mx-auto mt-5" aria-labelledby="todays-news-heading">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h3 fw-bold mb-0" id="todays-news-heading">Today's News</h2>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('news') }}">View all news</a>
            </div>

            <div class="vstack gap-3">
                @forelse ($newsArticles as $article)
                    @include('news._article')
                @empty
                    <div class="card shadow-sm card-body px-4 py-4 text-body-secondary">
                        No articles have been collected from your RSS subscriptions yet.
                    </div>
                @endforelse
            </div>
        </section>
        <!-- Learning Track stuff  -->

    </main>
@endsection
