<nav class="crm-tabs" aria-label="CRM tools">
    @foreach (['crm.tools.index' => 'Backups', 'crm.tools.import' => 'Import', 'crm.tools.export' => 'Export', 'crm.statuses.index' => 'Statuses', 'crm.tools.openAiPlatform' => 'OpenAI', 'crm.tools.googlePlatform' => 'Google Platform'] as $routeName => $label)
        <a class="{{ request()->routeIs($routeName) ? 'active' : '' }}"
            href="{{ route($routeName) }}">{{ $label }}</a>
    @endforeach
</nav>
