<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('learning') }}">Learning Center</a>
        <div class="navbar-nav flex-row gap-3">
            <div class="nav-item dropdown">
                <button
                    class="nav-link dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Tools
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="nav-link {{ request()->routeIs('learning.career-paths.*') ? 'active' : '' }}" href="{{ route('learning.career-paths.index') }}">Career Paths</a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('learning.agenda*') ? 'active' : '' }}" href="{{ route('learning.agenda') }}">Topic agenda</a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('learning.certificates.*') ? 'active' : '' }}" href="{{ route('learning.certificates.index') }}">Certificates</a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('learning.projects.*') ? 'active' : '' }}" href="{{ route('learning.projects.index') }}">Portfolio Projects</a>
                    </li>
                    <li>
                        <a class="nav-link {{ request()->routeIs('learning.decks.*', 'learning.cards.*') ? 'active' : '' }}" href="{{ route('learning.decks.index') }}">Flash Cards</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
