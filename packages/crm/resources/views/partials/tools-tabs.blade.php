<nav class="crm-tabs" aria-label="CRM tools">
    @foreach (['tools.index' => 'Backups', 'tools.import' => 'Import', 'tools.export' => 'Export', 'statuses.index' => 'Statuses', 'tools.openAiPlatform' => 'OpenAI', 'tools.googlePlatform' => 'Google Platform'] as $routeName => $label)
        <a class="{{ request()->routeIs($routeName) ? 'active' : '' }}"
            href="{{ route($routeName) }}">{{ $label }}</a>
    @endforeach
</nav>
