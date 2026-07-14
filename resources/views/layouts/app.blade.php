<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', config('app.name'))</title>

        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
            rel="stylesheet"
        >
    </head>
    <body class="bg-light">
        @php
            $navigationDate = $selectedDate
                ?? now((string) config('app.display_timezone', 'America/Chicago'))->startOfDay();
        @endphp

        <nav class="navbar bg-white border-bottom shadow-sm">
            <div class="container d-flex gap-4">
                <a class="navbar-brand fw-bold mb-0" href="{{ route('home') }}">
                    {{ config('app.name') }}
                </a>

                <div class="navbar-nav flex-row gap-3">
                    <a
                        class="nav-link {{ request()->routeIs('cron') ? 'active fw-semibold' : '' }}"
                        href="{{ route('cron') }}"
                    >Cron</a>
                    <a
                        class="nav-link {{ request()->routeIs('applied-to') ? 'active fw-semibold' : '' }}"
                        href="{{ route('applied-to') }}"
                    >Applied to</a>
                </div>

                <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2 ms-auto">
                    <label for="job-date" class="form-label mb-0 text-nowrap fw-semibold">Choose date</label>
                    <input
                        type="date"
                        class="form-control"
                        id="job-date"
                        name="date"
                        value="{{ $navigationDate->toDateString() }}"
                        aria-label="Choose a job scrape date"
                        onchange="this.form.submit()"
                    >
                    <noscript>
                        <button type="submit" class="btn btn-primary">View</button>
                    </noscript>
                </form>
            </div>
        </nav>

        @yield('content')
    </body>
</html>
