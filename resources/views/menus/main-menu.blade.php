<nav class="navbar app-navbar shadow-sm" aria-label="Primary navigation">
    <div class="d-flex flex-wrap align-items-center gap-1 px-2">
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('home')])
            href="{{ route('home') }}"
            @if (request()->routeIs('home')) aria-current="page" @endif
            aria-label="Dashboard"
            title="Dashboard">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true">
                <path
                    d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/>
                <path
                    d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12" />
                <path
                    d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17" />
            </svg>
            <span>Dashboard</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('news')])
            href="{{ route('news') }}"
            @if (request()->routeIs('news')) aria-current="page" @endif
            aria-label="News"
            title="News">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 640 640"
                fill="currentColor"
                aria-hidden="true">
                <!-- Font Awesome Free 7.3.1, CC BY 4.0: https://fontawesome.com/license/free -->
                <path
                    d="M96 128C96 110.3 110.3 96 128 96C357.8 96 544 282.2 544 512C544 529.7 529.7 544 512 544C494.3 544 480 529.7 480 512C480 317.6 322.4 160 128 160C110.3 160 96 145.7 96 128zM96 480C96 444.7 124.7 416 160 416C195.3 416 224 444.7 224 480C224 515.3 195.3 544 160 544C124.7 544 96 515.3 96 480zM128 224C287.1 224 416 352.9 416 512C416 529.7 401.7 544 384 544C366.3 544 352 529.7 352 512C352 388.3 251.7 288 128 288C110.3 288 96 273.7 96 256C96 238.3 110.3 224 128 224z"/>
            </svg>
            <span>News</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('jobs.*')])
            href="{{ route('jobs.index') }}"
            @if (request()->routeIs('jobs.*')) aria-current="page" @endif
            aria-label="Jobs"
            title="Jobs">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true">
                <path d="M12 12h.01" />
                <path d="M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                <path d="M22 13a18.15 18.15 0 0 1-20 0" />
                <rect
                    width="20"
                    height="14"
                    x="2"
                    y="6"
                    rx="2"/>
            </svg>
            <span>Jobs</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('crm.*')])
            href="{{ route('crm.companies.index') }}"
            @if (request()->routeIs('crm.*')) aria-current="page" @endif
            aria-label="CRM"
            title="CRM">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 640 640"
                fill="currentColor"
                aria-hidden="true">
                <!-- Font Awesome Free 7.3.1, CC BY 4.0: https://fontawesome.com/license/free -->
                <path
                    d="M192 112C183.2 112 176 119.2 176 128L176 512C176 520.8 183.2 528 192 528L272 528L272 448C272 430.3 286.3 416 304 416L336 416C353.7 416 368 430.3 368 448L368 528L448 528C456.8 528 464 520.8 464 512L464 128C464 119.2 456.8 112 448 112L192 112zM128 128C128 92.7 156.7 64 192 64L448 64C483.3 64 512 92.7 512 128L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM224 176C224 167.2 231.2 160 240 160L272 160C280.8 160 288 167.2 288 176L288 208C288 216.8 280.8 224 272 224L240 224C231.2 224 224 216.8 224 208L224 176zM368 160L400 160C408.8 160 416 167.2 416 176L416 208C416 216.8 408.8 224 400 224L368 224C359.2 224 352 216.8 352 208L352 176C352 167.2 359.2 160 368 160zM224 304C224 295.2 231.2 288 240 288L272 288C280.8 288 288 295.2 288 304L288 336C288 344.8 280.8 352 272 352L240 352C231.2 352 224 344.8 224 336L224 304zM368 288L400 288C408.8 288 416 295.2 416 304L416 336C416 344.8 408.8 352 400 352L368 352C359.2 352 352 344.8 352 336L352 304C352 295.2 359.2 288 368 288z"/>
            </svg>
            <span>CRM</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('bible')])
            href="{{ route('bible') }}"
            @if (request()->routeIs('bible')) aria-current="page" @endif
            aria-label="Bible"
            title="Bible">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 640 640"
                fill="currentColor"
                aria-hidden="true">
                <!-- Font Awesome Free 7.3.1, CC BY 4.0: https://fontawesome.com/license/free -->
                <path
                    d="M192 576C139 576 96 533 96 480L96 160C96 107 139 64 192 64L496 64C522.5 64 544 85.5 544 112L544 400C544 420.9 530.6 438.7 512 445.3L512 512C529.7 512 544 526.3 544 544C544 561.7 529.7 576 512 576L192 576zM192 448C174.3 448 160 462.3 160 480C160 497.7 174.3 512 192 512L448 512L448 448L192 448zM288 144L288 192L240 192C231.2 192 224 199.2 224 208L224 240C224 248.8 231.2 256 240 256L288 256L288 368C288 376.8 295.2 384 304 384L336 384C344.8 384 352 376.8 352 368L352 256L400 256C408.8 256 416 248.8 416 240L416 208C416 199.2 408.8 192 400 192L352 192L352 144C352 135.2 344.8 128 336 128L304 128C295.2 128 288 135.2 288 144z"/>
            </svg>
            <span>Bible</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('learning', 'learning.*')])
            href="{{ route('learning') }}"
            @if (request()->routeIs('learning', 'learning.*')) aria-current="page" @endif
            aria-label="Learning"
            title="Learning">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true">
                <path
                    d="M21.42 10.922a1 1 0 0 0-.55-.89l-8.5-4.25a1 1 0 0 0-.74 0l-8.5 4.25a1 1 0 0 0 0 1.79l8.5 4.25a1 1 0 0 0 .74 0l8.5-4.25a1 1 0 0 0 .55-.898Z"/>
                <path d="M22 10v6" />
                <path d="M6 13.6v4.1c0 .55.45 1 1 1h10c.55 0 1-.45 1-1v-4.1" />
            </svg>
            <span>Learning</span>
        </a>
        <a
            @class(['app-navbar-icon', 'is-active' => request()->routeIs('settings', 'settings.*')])
            href="{{ route('settings') }}"
            @if (request()->routeIs('settings', 'settings.*')) aria-current="page" @endif
            aria-label="Settings"
            title="Settings">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true">
                <path
                    d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"/>
                <circle
                    cx="12"
                    cy="12"
                    r="3"/>
            </svg>
            <span>Settings</span>
        </a>
    </div>
</nav>
