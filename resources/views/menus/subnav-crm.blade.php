<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('crm.companies.index') }}">CRM</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link {{ request()->routeIs('crm.saved-searches.*') ? 'active' : '' }}"
                href="{{ route('crm.saved-searches.index') }}">Saved Searches</a>
            <a class="nav-link {{ request()->routeIs('crm.my-business.*') ? 'active' : '' }}"
                href="{{ route('crm.my-business.index') }}">My Business</a>
            <a class="nav-link {{ request()->routeIs('crm.tools.*', 'crm.statuses.*') ? 'active' : '' }}"
                href="{{ route('crm.tools.index') }}">Tools</a>
        </div>
        <div class="navbar-nav ms-auto">
            <a class="nav-link {{ request()->routeIs('crm.companies.create') ? 'active' : '' }}"
                href="{{ route('crm.companies.create') }}">Add Company</a>
        </div>
    </div>
</nav>
