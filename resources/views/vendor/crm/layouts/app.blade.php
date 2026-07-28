<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM') | {{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/crm/crm.css') }}">
    @stack('styles')
</head>
<body>
    <nav class="crm-nav" aria-label="CRM navigation"><div class="crm-nav-inner">
        <a class="crm-brand" href="{{ route('companies.index') }}">CRM</a>
        <a class="{{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Companies</a>
        <a class="{{ request()->routeIs('saved-searches.*') ? 'active' : '' }}" href="{{ route('saved-searches.index') }}">Saved Searches</a>
        <a class="{{ request()->routeIs('my-business.*') ? 'active' : '' }}" href="{{ route('my-business.index') }}">My Business</a>
        <a class="{{ request()->routeIs('tools.*', 'statuses.*') ? 'active' : '' }}" href="{{ route('tools.index') }}">Tools</a>
    </div></nav>
    <header class="crm-header"><div><h1>@yield('heading', 'CRM')</h1><p>@yield('subtitle')</p></div><div class="crm-actions">@yield('actions')</div></header>
    <main class="crm-main">
        @if(session('success'))<div class="crm-flash">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="crm-flash crm-flash-error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="crm-flash crm-flash-error"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
    <script src="{{ asset('vendor/crm/crm.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
