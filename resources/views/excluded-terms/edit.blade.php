@extends('layouts.app')

@section('title', 'Edit excluded term | '.config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 760px;">
        <h1 class="display-6 fw-bold mb-4">Edit excluded term</h1>
        <form method="POST" action="{{ route('excluded-terms.update', $excludedTerm) }}" class="card card-body shadow-sm">
            @csrf @method('PUT')
            <label class="form-label fw-semibold" for="term">Term</label>
            <input class="form-control @error('term') is-invalid @enderror" id="term" name="term" value="{{ old('term', $excludedTerm->term) }}" required>
            @error('term') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="d-flex gap-2 mt-4"><button class="btn btn-primary" type="submit">Save changes</button><a class="btn btn-outline-secondary" href="{{ route('excluded-terms.index') }}">Cancel</a></div>
        </form>
    </main>
@endsection
