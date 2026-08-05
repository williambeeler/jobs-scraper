<nav class="nav nav-pills flex-wrap gap-2 mb-4" aria-label="CRM tools">
    @foreach (['crm.statuses.index' => 'Statuses', 'crm.tools.openAiPlatform' => 'OpenAI', 'crm.tools.googlePlatform' => 'Google Platform'] as $routeName => $label)
        <a class="nav-link {{ request()->routeIs($routeName) ? 'active' : '' }}"
            href="{{ route($routeName) }}"
        >{{ $label }}</a>
    @endforeach
</nav>
