<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('learning') }}">Learning Center</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link {{ request()->routeIs('learning') ? 'active' : '' }}"
                href="{{ route('learning') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('learning.agenda*') ? 'active' : '' }}"
                href="{{ route('learning.agenda') }}">Topic agenda</a>
            <a class="nav-link {{ request()->routeIs('learning.certificates.*') ? 'active' : '' }}"
                href="{{ route('learning.certificates.index') }}">Certificates</a>
        </div>
    </div>
</nav>
