<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('settings.backup') }}">Settings</a>
        <div class="navbar-nav flex-row gap-3">
            <a class="nav-link" href="{{ route('settings.backup') }}">Backup</a>
            <a class="nav-link" href="{{ route('settings.restore') }}">Restore</a>
        </div>
    </div>
</nav>