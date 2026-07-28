<nav class="navbar navbar-expand bg-white border-bottom shadow-sm">
    <div class="container d-flex gap-4">
        <a class="navbar-brand fw-bold mb-0" href="{{ route('jobs') }}">Job Scraper</a>
        <div class="navbar-nav flex-row gap-3">
            <div class="nav-item dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Tools
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('applied-to') }}">Applied to</a></li>
                    <li><a class="dropdown-item" href="{{ route('calendar') }}">Calendar</a></li>
                    <li><a class="dropdown-item" href="{{ route('kanban') }}">Kanban</a></li>
                    <li><a class="dropdown-item" href="{{ route('job-search') }}">Job Search</a></li>
                </ul>
            </div>
            <div class="nav-item dropdown">
                <button class="nav-link dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Settings
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('company-preferences.index') }}">Companies List</a></li>
                    <li><a class="dropdown-item" href="{{ route('cron') }}">Cron</a></li>
                    <li><a class="dropdown-item" href="{{ route('excluded-terms.index') }}">Excluded terms</a></li>
                </ul>
            </div>
        </div>
        <form method="GET" action="{{ route('jobs') }}" class="d-flex align-items-center gap-2 ms-auto">
            <label for="job-date" class="form-label mb-0 text-nowrap fw-semibold">Choose date</label>
            <input type="date" class="form-control" id="job-date" name="date" value="{{ $navigationDate->toDateString() }}" aria-label="Choose a job scrape date" onchange="this.form.submit()">
            <noscript><button type="submit" class="btn btn-primary">View</button></noscript>
        </form>
    </div>
</nav>    