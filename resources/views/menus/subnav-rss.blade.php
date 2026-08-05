<nav class="navbar navbar-expand bg-white border-bottom shadow-sm" aria-label="News navigation">
    <div class="container d-flex flex-wrap gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('news') }}">News</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link {{ request()->routeIs('news') ? 'active' : '' }}"
                href="{{ route('news') }}"
            >Latest</a>
            <a class="nav-link {{ request()->routeIs('news.feeds.*') ? 'active' : '' }}"
                href="{{ route('news.feeds.index') }}"
            >Feeds</a>
        </div>
        <form
            class="ms-auto"
            method="POST"
            action="{{ route('news.feeds.refresh-all') }}"
        >
            @csrf
            <button class="btn btn-sm btn-outline-primary" type="submit">Refresh all feeds</button>
        </form>
    </div>
</nav>
