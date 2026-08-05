<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name'))</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
            rel="stylesheet">
        <style>
            .job-row-whitelisted {
                position: relative;
                background: linear-gradient(to bottom, #fff 15%, #fff8df 100%);
                border-left: 4px solid #d5a72e;
            }

            .job-row-verified {
                background: linear-gradient(to bottom, #fff 15%, #e8f5ff 100%);
                border-left: 4px solid #63aee8;
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
                gap: .45rem;
                height: 2.5rem;
                padding: 0 .75rem;
                border-radius: .625rem;
                font-size: .875rem;
                font-weight: 500;
                text-decoration: none;
                white-space: nowrap;
            }

            .app-navbar-icon:hover,
            .app-navbar-icon:focus-visible,
            .app-navbar-icon.is-active {
                color: #fff;
                background: rgba(255, 255, 255, .1);
            }

            .app-navbar-icon.is-active {
                box-shadow: inset 0 -2px 0 #60a5fa;
            }

            .app-navbar-icon svg {
                flex-shrink: 0;
                width: 1.25rem;
                height: 1.25rem;
            }
        </style>
        @stack('styles')
    </head>

    <body class="bg-light">
        @php
            $navigationDate =
                $selectedDate ??
                now((string) config('app.display_timezone', 'America/Chicago'))->startOfDay();
        @endphp

        @include('menus.main-menu')

        @unless (request()->routeIs('home'))
            @if (request()->routeIs('crm.*'))
                @include('menus.subnav-crm')
            @elseif (request()->routeIs('learning', 'learning.*'))
                @include('menus.subnav-learning')
            @elseif (request()->routeIs('settings.*'))
                @include('menus.subnav-settings')
            @elseif (request()->routeIs('jobs.*'))
                @include('menus.subnav-job-scraper')
            @else

            @endif
        @endunless

        @yield('content')

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        @stack('scripts')
    </body>

</html>
