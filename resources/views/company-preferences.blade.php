@extends('layouts.app')

@section('title', 'Company preferences | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Job filtering</p>
            <h1 class="display-5 fw-bold mb-2">Company preferences</h1>
            <p class="text-secondary mb-0">Remove a preference here to allow the company to appear normally again.</p>
        </header>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @forelse ($preferences->groupBy(fn ($preference) => $preference->type->value) as $type => $items)
            <section class="mb-4">
                <h2 class="h4 text-capitalize">{{ $type }}ed companies</h2>
                <div class="list-group shadow-sm">
                    @foreach ($items as $preference)
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                            <span>{{ $preference->company_name }}</span>
                            <form method="POST" action="{{ route('company-preferences.destroy', $preference) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="alert alert-secondary">No companies have been whitelisted or blacklisted yet.</div>
        @endforelse
    </main>
@endsection
