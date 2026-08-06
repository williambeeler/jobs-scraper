<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('bible') }}">Bible</a>

        <a class="nav-link {{ request()->routeIs('bible.worksheets') ? 'active' : '' }}" href="{{ route('bible.worksheets') }}">Worksheets</a>
    </div>
</nav>
