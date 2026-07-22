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
        <style>
            .job-row-whitelisted {
                position: relative;
                background: linear-gradient(to bottom, #fff 15%, #fff8df 100%);
                border-left: 4px solid #d5a72e;
            }
            .job-row-star {
                color: #b8860b;
                font-size: 1.15rem;
                line-height: 1;
            }
            .app-navbar {
                background: #111827;
            }
            .app-navbar-icon {
                color: #b7c3d7;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: .625rem;
            }
            .app-navbar-icon:hover,
            .app-navbar-icon:focus-visible {
                color: #fff;
                background: rgba(255, 255, 255, .1);
            }
            .app-navbar-icon svg {
                width: 1.25rem;
                height: 1.25rem;
            }
        </style>
    </head>
    <body class="bg-light">
        @php
            $navigationDate = $selectedDate
                ?? now((string) config('app.display_timezone', 'America/Chicago'))->startOfDay();
        @endphp

        <nav class="navbar app-navbar shadow-sm" aria-label="Primary navigation">
            <div class="d-flex align-items-center gap-1 px-2">
                <a class="app-navbar-icon" href="{{ route('home') }}" aria-label="Home" title="Home">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/></svg>
                </a>
                <a class="app-navbar-icon" href="{{ route('jobs') }}" aria-label="Jobs" title="Jobs">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12h.01"/><path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M22 13a18.15 18.15 0 0 1-20 0"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                </a>
                <a class="app-navbar-icon" href="{{ route('bible') }}" aria-label="Bible" title="Bible">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 14h6"/><path d="M12 17v-6"/></svg>
                </a>
                <a class="app-navbar-icon" href="{{ route('learning') }}" aria-label="Learning" title="Learning">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.42 10.922a1 1 0 0 0-.55-.89l-8.5-4.25a1 1 0 0 0-.74 0l-8.5 4.25a1 1 0 0 0 0 1.79l8.5 4.25a1 1 0 0 0 .74 0l8.5-4.25a1 1 0 0 0 .55-.898Z"/><path d="M22 10v6"/><path d="M6 13.6v4.1c0 .55.45 1 1 1h10c.55 0 1-.45 1-1v-4.1"/></svg>
                </a>
                <a class="app-navbar-icon" href="{{ route('settings') }}" aria-label="Settings" title="Settings">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
            </div>
        </nav>

        @unless (request()->routeIs('home'))
        <nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
            <div class="container d-flex gap-4">
                @if (request()->routeIs('settings.*'))
                    <a class="navbar-brand fw-bold mb-0" href="{{ route('settings.backup') }}">Settings</a>
                    <div class="navbar-nav flex-row gap-3">
                        <a class="nav-link" href="{{ route('settings.backup') }}">Backup</a>
                        <a class="nav-link" href="{{ route('settings.restore') }}">Restore</a>
                    </div>
                @else
                    <a class="navbar-brand fw-bold mb-0" href="{{ route('jobs') }}">Job Scraper</a>
                    <div class="navbar-nav flex-row gap-3">
                        <div class="nav-item dropdown">
                            <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Tools
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('applied-to') }}">Applied to</a></li>
                            </ul>
                        </div>
                        <div class="nav-item dropdown">
                            <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Settings
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('company-preferences.index') }}">Companies List</a></li>
                                <li><a class="dropdown-item" href="{{ route('cron') }}">Cron</a></li>
                                <li><a class="dropdown-item" href="{{ route('excluded-terms.index') }}">Excluded terms</a></li>
                            </ul>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('jobs') }}" class="d-flex align-items-center gap-2 ms-auto">
                        <label for="job-date" class="form-label mb-0 text-nowrap fw-semibold">Choose date</label>
                        <input type="date" class="form-control" id="job-date" name="date" value="{{ $navigationDate->toDateString() }}" aria-label="Choose a job scrape date" onchange="this.form.submit()">
                        <noscript><button type="submit" class="btn btn-primary">View</button></noscript>
                    </form>
                @endif
            </div>
        </nav>
        @endunless

        @yield('content')

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        @stack('scripts')
    </body>
</html>
