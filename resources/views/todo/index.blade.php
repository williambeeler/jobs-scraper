@extends('layouts.app')

@section('title', 'Todo boards | ' . config('app.name'))

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="mb-2 text-uppercase text-secondary fw-semibold small">Todo</p>
            <h1 class="display-5 fw-bold mb-2">Boards</h1>
            <p class="text-secondary mb-0">Organize cards into a workflow that fits each project.</p>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <section class="card shadow-sm mb-5" aria-labelledby="new-board-title">
            <div class="card-body p-4">
                <h2 class="h4" id="new-board-title">Start a new board</h2>
                <p class="text-body-secondary">New boards begin with Backlog, In Progress, and Done.</p>
                <form method="POST" action="{{ route('todo.boards.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label" for="board-name">Board name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="board-name" name="name" value="{{ old('name') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="board-description">Description</label>
                        <input class="form-control" id="board-description" name="description" value="{{ old('description') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" type="submit">Create board</button>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="show_card_count" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="board-show-card-count" name="show_card_count" value="1" @checked(old('show_card_count', true))>
                            <label class="form-check-label" for="board-show-card-count">Show card count</label>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section aria-labelledby="your-boards-title">
            <h2 class="h3 mb-3" id="your-boards-title">Your boards</h2>
            <div class="row g-3">
                @forelse ($boards as $board)
                    <div class="col-md-6 col-xl-4">
                        <a class="card shadow-sm h-100 text-decoration-none text-body" href="{{ route('todo.boards.show', $board) }}">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between gap-3">
                                    <h3 class="h5 mb-2">{{ $board->name }}</h3>
                                    <span class="badge text-bg-secondary align-self-start">
                                        {{ trans_choice(':count card|:count cards', $board->cards_count, ['count' => $board->cards_count]) }}
                                    </span>
                                </div>
                                <p class="text-body-secondary mb-0">{{ $board->description ?: 'No description yet.' }}</p>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12"><div class="alert alert-secondary">Create your first board to get started.</div></div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
