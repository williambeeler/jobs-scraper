@extends('layouts.app')

@section('title', 'Learning Center | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p>
                <h1 class="display-5 fw-bold mb-2">Topic agenda</h1>
                <p class="text-secondary mb-0">Keep track of the technologies and ideas you want to add
                    to your knowledge base.</p>
            </div>
            <div class="d-flex gap-2 align-items-start" aria-label="Topic totals">
                @foreach ($statuses as $value => $label)
                    <span
                        class="badge rounded-pill text-bg-{{ $value === 'completed' ? 'success' : ($value === 'in_progress' ? 'primary' : 'secondary') }}">
                        {{ $label }} {{ $topicCounts[$value] ?? 0 }}
                    </span>
                @endforeach
            </div>
        </header>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <section class="card card-body shadow-sm mb-4" aria-labelledby="add-topic-heading">
            <h2 class="h5 mb-3" id="add-topic-heading">Add a topic</h2>
            <form method="POST" action="{{ route('learning-topics.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-5">
                        <label class="form-label fw-semibold" for="title">Topic</label>
                        <input
                            class="form-control @error('title') is-invalid @enderror"
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            placeholder="e.g. ColdFusion or advanced React patterns"
                            required
                            autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="status">Status</label>
                        <select
                            class="form-select @error('status') is-invalid @enderror"
                            id="status"
                            name="status">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'planned') === $value)>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label fw-semibold" for="priority">Priority</label>
                        <select
                            class="form-select @error('priority') is-invalid @enderror"
                            id="priority"
                            name="priority">
                            @foreach ($priorities as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Add to agenda</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="description">Notes <span
                                class="text-secondary fw-normal"
                            >(optional)</span></label>
                        <textarea
                            class="form-control @error('description') is-invalid @enderror"
                            id="description"
                            name="description"
                            rows="2"
                            placeholder="What do you want to learn or build?"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </form>
        </section>

        <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Filter topics">
            <a class="btn btn-sm {{ $status === '' ? 'btn-dark' : 'btn-outline-dark' }}"
                href="{{ route('learning') }}"
            >All</a>
            @foreach ($statuses as $value => $label)
                <a class="btn btn-sm {{ $status === $value ? 'btn-dark' : 'btn-outline-dark' }}"
                    href="{{ route('learning', ['status' => $value]) }}"
                >{{ $label }}</a>
            @endforeach
        </div>

        <section class="row g-3" aria-label="Learning topics">
            @forelse ($topics as $topic)
                <div class="col-12 col-lg-6">
                    <article class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                <h2 class="h5 mb-0">{{ $topic->title }}</h2>
                                <span
                                    class="badge text-bg-{{ $topic->priority === 'high' ? 'danger' : ($topic->priority === 'medium' ? 'warning' : 'light') }}">
                                    {{ $priorities[$topic->priority] }} priority
                                </span>
                            </div>
                            <p class="mb-3">
                                <span
                                    class="badge text-bg-{{ $topic->status === 'completed' ? 'success' : ($topic->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                    {{ $statuses[$topic->status] }}
                                </span>
                            </p>
                            @if ($topic->description)
                                <p class="text-secondary mb-0" style="white-space: pre-line;">
                                    {{ $topic->description }}</p>
                            @else
                                <p class="text-secondary fst-italic mb-0">No notes yet.</p>
                            @endif
                        </div>
                        <footer class="card-footer bg-white d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-secondary"
                                href="{{ route('learning-topics.edit', $topic) }}"
                            >Edit</a>
                            <form
                                method="POST"
                                action="{{ route('learning-topics.destroy', $topic) }}"
                                onsubmit="return confirm('Delete this learning topic?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"
                                    type="submit">Delete</button>
                            </form>
                        </footer>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card card-body text-center text-secondary shadow-sm py-5">
                        {{ $status ? 'No topics have this status yet.' : 'Your learning agenda is empty. Add the first topic above.' }}
                    </div>
                </div>
            @endforelse
        </section>
    </main>
@endsection
