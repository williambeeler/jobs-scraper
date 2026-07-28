<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DailyDash Dashboard' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('packages/dailydash/jobs/resources/css/dashboard.css')

    @stack('styles')
</head>
<body>
    @php
        $routeName = request()->route()?->getName() ?? '';
        $isDashboard = $routeName === 'dashboard.index';
        $isJobsSection = str_starts_with($routeName, 'jobs.job-');
        $isBibleSection = str_starts_with($routeName, 'bible.');
        $isSettings = $routeName === 'settings.index';
        $showSidebar = ! ($hideSidebar ?? false);
        $bibleRoute = \Illuminate\Support\Facades\Route::has('bible.reading.index') ? route('bible.reading.index') : url('/bible');
        $bibleMenu = [
            [
                'label' => 'Reading Plan',
                'route' => $bibleRoute,
                'active' => $isBibleSection,
            ],
        ];
    @endphp

    <div class="app-shell {{ $showSidebar ? '' : 'app-shell--compact' }}">
        <aside class="app-rail">
            <nav class="app-rail-nav" aria-label="Primary navigation">
                <a class="app-rail-link {{ $isDashboard ? 'is-active' : '' }}" href="{{ url('/') }}" aria-label="Dashboard">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                        <path d="M296.5 69.2C311.4 62.3 328.6 62.3 343.5 69.2L562.1 170.2C570.6 174.1 576 182.6 576 192C576 201.4 570.6 209.9 562.1 213.8L343.5 314.8C328.6 321.7 311.4 321.7 296.5 314.8L77.9 213.8C69.4 209.8 64 201.3 64 192C64 182.7 69.4 174.1 77.9 170.2L296.5 69.2zM112.1 282.4L276.4 358.3C304.1 371.1 336 371.1 363.7 358.3L528 282.4L562.1 298.2C570.6 302.1 576 310.6 576 320C576 329.4 570.6 337.9 562.1 341.8L343.5 442.8C328.6 449.7 311.4 449.7 296.5 442.8L77.9 341.8C69.4 337.8 64 329.3 64 320C64 310.7 69.4 302.1 77.9 298.2L112 282.4zM77.9 426.2L112 410.4L276.3 486.3C304 499.1 335.9 499.1 363.6 486.3L527.9 410.4L562 426.2C570.5 430.1 575.9 438.6 575.9 448C575.9 457.4 570.5 465.9 562 469.8L343.4 570.8C328.5 577.7 311.3 577.7 296.4 570.8L77.9 469.8C69.4 465.8 64 457.3 64 448C64 438.7 69.4 430.1 77.9 426.2z"/>
                    </svg>
                </a>
                <a class="app-rail-link" href="{{ route('jobs.job-sources.index') }}" aria-label="Jobs">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7V5.75A1.75 1.75 0 0 1 9.75 4h4.5A1.75 1.75 0 0 1 16 5.75V7" />
                        <rect x="4" y="7" width="16" height="11" rx="2" />
                        <path d="M4 11.5h16" />
                        <path d="M10 14h4" />
                    </svg>
                </a>
                <a class="app-rail-link {{ $isBibleSection ? 'is-active' : '' }}" href="{{ $bibleRoute }}" aria-label="Bible Reading">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M192 576C139 576 96 533 96 480L96 160C96 107 139 64 192 64L496 64C522.5 64 544 85.5 544 112L544 400C544 420.9 530.6 438.7 512 445.3L512 512C529.7 512 544 526.3 544 544C544 561.7 529.7 576 512 576L192 576zM192 448C174.3 448 160 462.3 160 480C160 497.7 174.3 512 192 512L448 512L448 448L192 448zM288 144L288 192L240 192C231.2 192 224 199.2 224 208L224 240C224 248.8 231.2 256 240 256L288 256L288 368C288 376.8 295.2 384 304 384L336 384C344.8 384 352 376.8 352 368L352 256L400 256C408.8 256 416 248.8 416 240L416 208C416 199.2 408.8 192 400 192L352 192L352 144C352 135.2 344.8 128 336 128L304 128C295.2 128 288 135.2 288 144z"/></svg>
                </a>
                <a class="app-rail-link {{ $isSettings ? 'is-active' : '' }}" href="{{ route('settings.index') }}" aria-label="Settings">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3.25" />
                        <path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a2 2 0 1 1-4 0v-.2a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4a2 2 0 1 1 0-4h.2a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1 1 0 0 0 1.1.2 1 1 0 0 0 .6-.9V4a2 2 0 1 1 4 0v.2a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.2a2 2 0 1 1 0 4h-.2a1 1 0 0 0-.9.6z" />
                    </svg>
                </a>
            </nav>
        </aside>

        @if($showSidebar)
            <aside class="app-sidebar">
                <div class="app-sidebar-header">
                    <div class="app-sidebar-kicker">Workspace</div>
                    <div class="app-sidebar-title">Daily Dashboard</div>
                </div>
                <div class="app-sidebar-group">
                    <div class="app-sidebar-group-label">Bible</div>
                    <nav class="app-menu" aria-label="Bible navigation">
                        @foreach($bibleMenu as $item)
                            <a class="app-menu-link {{ $item['active'] ? 'is-active' : '' }}" href="{{ $item['route'] }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>
        @endif

        <div class="app-main">
            <main class="app-content">
                @yield('content')
            </main>

            <footer class="app-footer">
                <div>&copy; {{ now()->year }} DailyDash</div>
            </footer>
        </div>
    </div>

    @vite('packages/dailydash/jobs/resources/js/dashboard.js')

    @stack('scripts')
</body>
</html>
