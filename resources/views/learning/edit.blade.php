@extends('layouts.app')

@section('title', 'Edit ' . $topic->title . ' | ' . config('app.name'))

@section('content')
    <main class="container py-5" style="max-width: 760px;">
        <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
        <h1 class="display-6 fw-bold mb-4">Edit topic</h1>

        <form
            method="POST"
            action="{{ route('learning.agenda.topics.update', $topic) }}"
            class="card card-body shadow-sm">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold" for="title">Topic</label>
                <input
                    class="form-control @error('title') is-invalid @enderror"
                    id="title"
                    name="title"
                    value="{{ old('title', $topic->title) }}"
                    required
                    autofocus>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" for="status">Status</label>
                    <select
                        class="form-select @error('status') is-invalid @enderror"
                        id="status"
                        name="status">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $topic->status) === $value)>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold" for="priority">Priority</label>
                    <select
                        class="form-select @error('priority') is-invalid @enderror"
                        id="priority"
                        name="priority">
                        @foreach ($priorities as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', $topic->priority) === $value)>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold" for="description">Notes</label>
                <textarea
                    class="form-control @error('description') is-invalid @enderror"
                    id="description"
                    name="description"
                    rows="6"
                >{{ old('description', $topic->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save changes</button>
                <a class="btn btn-outline-secondary" href="{{ route('learning.agenda') }}">Cancel</a>
            </div>
        </form>
    </main>
@endsection
