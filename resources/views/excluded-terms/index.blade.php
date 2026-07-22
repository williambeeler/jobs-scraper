@extends('layouts.app')

@section('title', 'Excluded terms | '.config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Job filtering</p>
            <h1 class="display-5 fw-bold mb-2">Excluded terms ({{ $terms->count() }})</h1>
            <p class="text-secondary mb-0">Jobs whose title contains an excluded term are marked Not Interested and hidden everywhere.</p>
        </header>

        @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

        <form method="POST" action="{{ route('excluded-terms.store') }}" class="card card-body mb-4 shadow-sm">
            @csrf
            <label class="form-label fw-semibold" for="term">Add an excluded term</label>
            <div class="d-flex gap-2">
                <input class="form-control @error('term') is-invalid @enderror" id="term" name="term" value="{{ old('term') }}" placeholder="e.g. .NET" required>
                <button class="btn btn-primary text-nowrap" type="submit">Add term</button>
            </div>
            @error('term') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </form>

        <div class="list-group shadow-sm">
            @forelse ($terms as $term)
                <div class="list-group-item d-flex align-items-center justify-content-between gap-3">
                    <span>{{ $term->term }}</span>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('excluded-terms.edit', $term) }}">Edit</a>
                        <form method="POST" action="{{ route('excluded-terms.destroy', $term) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-secondary">No terms are excluded yet.</div>
            @endforelse
        </div>
    </main>
@endsection
