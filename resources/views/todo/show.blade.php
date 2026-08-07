@extends('layouts.app')

@section('title', $board->name . ' | Todo | ' . config('app.name'))

@section('content')
    <main class="container-fluid px-4 py-5">
        <header class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mb-2 text-uppercase text-secondary fw-semibold small"><a href="{{ route('todo') }}">Todo boards</a></p>
                <h1 class="display-5 fw-bold mb-1">{{ $board->name }}</h1>
                @if ($board->description)<p class="text-body-secondary mb-0">{{ $board->description }}</p>@endif
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#edit-board-modal">Edit board</button>
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#add-column-modal">+ Add column</button>
            </div>
        </header>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="todo-board" data-todo-board data-reorder-url="{{ route('todo.columns.reorder', $board) }}">
            @foreach ($board->columns as $column)
                <section class="todo-column" data-board-column data-column-id="{{ $column->id }}">
                    <header class="todo-column-header">
                        <button class="todo-column-drag-handle" type="button" data-column-drag-handle aria-label="Move {{ $column->name }} column" title="Drag to reorder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3 4.5A.5.5 0 0 1 3.5 4h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5Zm0 3A.5.5 0 0 1 3.5 7h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5Zm0 3a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5Z"/></svg>
                        </button>
                        <div class="todo-column-title-wrap">
                            <button class="todo-column-title" type="button" data-column-title>{{ $column->name }}</button>
                            <form method="POST" action="{{ route('todo.columns.update', [$board, $column]) }}" class="d-none todo-column-title-form" data-column-title-form>
                                @csrf
                                @method('PUT')
                                <input class="form-control form-control-sm fw-semibold" name="name" value="{{ $column->name }}" aria-label="Column name" required data-column-title-input>
                                <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-column-{{ $column->id }}" aria-label="Delete {{ $column->name }} column" title="Delete column">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/></svg>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-column-title-cancel aria-label="Cancel editing {{ $column->name }} column" title="Cancel">&times;</button>
                            </form>
                        </div>
                        @if ($board->show_card_count)
                            <span class="badge rounded-pill text-bg-secondary" data-card-count>{{ $column->cards->count() }}</span>
                        @endif
                    </header>

                    <div class="todo-card-list" data-card-list data-column-id="{{ $column->id }}">
                        @foreach ($column->cards as $card)
                            <article class="todo-card {{ $card->completed_at ? 'is-complete' : '' }}" data-card data-move-url="{{ route('todo.cards.move', [$board, $card]) }}">
                                <button class="todo-card-complete" type="button" data-card-complete data-complete-url="{{ route('todo.cards.complete', [$board, $card]) }}" aria-label="{{ $card->completed_at ? 'Mark incomplete' : 'Complete' }}" title="{{ $card->completed_at ? 'Mark incomplete' : 'Complete' }}">
                                    <span class="todo-card-complete-circle" aria-hidden="true">&#10003;</span>
                                </button>
                                <div class="todo-card-content">
                                    <h2 class="h6 fw-bold mb-1">{{ $card->title }}</h2>
                                    @if ($card->description)<p class="small text-body-secondary mb-0">{{ Str::limit(strip_tags($card->description), 160) }}</p>@endif
                                </div>
                                <button class="todo-card-edit-trigger" type="button" data-bs-toggle="modal" data-bs-target="#edit-card-{{ $card->id }}" aria-label="Edit {{ $card->title }}" title="Edit card">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M15.502 1.94a.5.5 0 0 1 0 .706l-.793.793-2-2 .793-.793a.5.5 0 0 1 .707 0l1.293 1.293ZM13.854 4.293l-2-2L4.939 9.207a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.915-6.915Z"/><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11Z"/></svg>
                                </button>
                            </article>
                        @endforeach
                    </div>

                    <button class="todo-add-card-trigger" type="button" data-add-card-trigger>
                        <span aria-hidden="true">+</span> Add a card
                    </button>
                    <form method="POST" action="{{ route('todo.cards.store', [$board, $column]) }}" class="todo-add-card-form d-none" data-add-card-form>
                        @csrf
                        <label class="visually-hidden" for="new-card-title-{{ $column->id }}">Card title</label>
                        <textarea class="form-control" id="new-card-title-{{ $column->id }}" name="title" rows="3" placeholder="Enter a title or paste a link" required data-add-card-title></textarea>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <button class="btn btn-primary" type="submit">Add card</button>
                            <button class="todo-add-card-cancel" type="button" aria-label="Cancel adding card" title="Cancel" data-add-card-cancel>&times;</button>
                        </div>
                    </form>
                </section>
            @endforeach
        </div>
    </main>

    @foreach ($board->cards as $card)
        <div class="modal fade" id="edit-card-{{ $card->id }}" tabindex="-1" aria-labelledby="edit-card-{{ $card->id }}-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable todo-card-modal-dialog"><div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="edit-card-{{ $card->id }}-title">Edit card</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <div class="col-lg-8 p-4 border-end todo-card-modal-main">
                            <form id="card-update-{{ $card->id }}" method="POST" action="{{ route('todo.cards.update', [$board, $card]) }}">
                                @csrf @method('PUT')
                                <label class="form-label fw-semibold" for="edit-card-title-{{ $card->id }}">Title</label>
                                <input class="form-control mb-3" id="edit-card-title-{{ $card->id }}" name="title" value="{{ $card->title }}" required>

                                <label class="form-label fw-semibold" for="edit-card-column-{{ $card->id }}">Column</label>
                                <select class="form-select mb-4" id="edit-card-column-{{ $card->id }}" name="board_column_id" required>
                                    @foreach ($board->columns as $column)
                                        <option value="{{ $column->id }}" @selected($column->id === $card->board_column_id)>{{ $column->name }}</option>
                                    @endforeach
                                </select>

                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <h3 class="h6 fw-bold mb-0">Description</h3>
                                </div>
                                <div class="note-editor border rounded mb-4" data-note-editor>
                                    <div class="note-editor-toolbar d-flex flex-wrap gap-1 border-bottom p-2" role="toolbar" aria-label="Description formatting">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="bold" aria-label="Bold"><strong>B</strong></button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="italic" aria-label="Italic"><em>I</em></button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="underline" aria-label="Underline"><u>U</u></button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="bulletList">Bullets</button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="orderedList">Numbers</button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="link">Link</button>
                                        <button class="btn btn-sm btn-outline-secondary ms-auto" type="button" data-note-command="undo" aria-label="Undo">Undo</button>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-note-command="redo" aria-label="Redo">Redo</button>
                                    </div>
                                    <div class="note-editor-surface" data-note-surface></div>
                                    <textarea class="visually-hidden" id="edit-card-description-{{ $card->id }}" name="description" data-note-input>{{ $card->description }}</textarea>
                                </div>
                            </form>

                            <section aria-labelledby="card-{{ $card->id }}-checklists">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <h3 class="h5 fw-bold mb-0" id="card-{{ $card->id }}-checklists">Checklists</h3>
                                    <form class="d-flex gap-2" method="POST" action="{{ route('todo.checklists.store', [$board, $card]) }}" data-card-modal-form>
                                        @csrf
                                        <input class="form-control form-control-sm" name="name" placeholder="Checklist name" aria-label="Checklist name" required>
                                        <button class="btn btn-sm btn-outline-primary text-nowrap" type="submit">Add checklist</button>
                                    </form>
                                </div>

                                @forelse ($card->checklists as $checklist)
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-2 mb-3">
                                                <form class="d-flex flex-grow-1 gap-2" method="POST" action="{{ route('todo.checklists.update', [$board, $card, $checklist]) }}" data-card-modal-form>
                                                    @csrf @method('PUT')
                                                    <input class="form-control form-control-sm fw-semibold" name="name" value="{{ $checklist->name }}" aria-label="Checklist name" required>
                                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Save</button>
                                                </form>
                                                <form method="POST" action="{{ route('todo.checklists.destroy', [$board, $card, $checklist]) }}" data-card-modal-form>
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            </div>
                                            @php
                                                $completedItems = $checklist->items->where('is_complete', true)->count();
                                                $progress = $checklist->items->isEmpty() ? 0 : (int) round(($completedItems / $checklist->items->count()) * 100);
                                            @endphp
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <span class="small text-body-secondary">{{ $progress }}%</span>
                                                <div class="progress flex-grow-1" role="progressbar" aria-label="Checklist progress" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                                </div>
                                            </div>
                                            @foreach ($checklist->items as $item)
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <form method="POST" action="{{ route('todo.checklist-items.toggle', [$board, $card, $checklist, $item]) }}" data-card-modal-form>
                                                        @csrf @method('PATCH')
                                                        <button class="btn btn-sm {{ $item->is_complete ? 'btn-success' : 'btn-outline-secondary' }} rounded-circle todo-checklist-toggle" type="submit" aria-label="{{ $item->is_complete ? 'Mark incomplete' : 'Mark complete' }}">&#10003;</button>
                                                    </form>
                                                    <form class="d-flex flex-grow-1 gap-2" method="POST" action="{{ route('todo.checklist-items.update', [$board, $card, $checklist, $item]) }}" data-card-modal-form>
                                                        @csrf @method('PUT')
                                                        <input class="form-control form-control-sm {{ $item->is_complete ? 'text-decoration-line-through text-body-secondary' : '' }}" name="text" value="{{ $item->text }}" aria-label="Checklist item" required>
                                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Save</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('todo.checklist-items.destroy', [$board, $card, $checklist, $item]) }}" data-card-modal-form>
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete checklist item">&times;</button>
                                                    </form>
                                                </div>
                                            @endforeach
                                            <form class="d-flex gap-2 mt-3" method="POST" action="{{ route('todo.checklist-items.store', [$board, $card, $checklist]) }}" data-card-modal-form>
                                                @csrf
                                                <input class="form-control form-control-sm" name="text" placeholder="Add an item" aria-label="New checklist item" required>
                                                <button class="btn btn-sm btn-primary text-nowrap" type="submit">Add item</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-body-secondary small mb-0">No checklists yet.</p>
                                @endforelse
                            </section>
                        </div>

                        <aside class="col-lg-4 p-4 bg-body-tertiary todo-card-comments" aria-labelledby="card-{{ $card->id }}-comments">
                            <h3 class="h5 fw-bold mb-3" id="card-{{ $card->id }}-comments">Comments</h3>
                            <form class="mb-4" method="POST" action="{{ route('todo.comments.store', [$board, $card]) }}" data-card-modal-form>
                                @csrf
                                <label class="visually-hidden" for="new-comment-{{ $card->id }}">Write a comment</label>
                                <textarea class="form-control mb-2" id="new-comment-{{ $card->id }}" name="body" rows="3" placeholder="Write a comment..." required></textarea>
                                <button class="btn btn-primary" type="submit">Add comment</button>
                            </form>
                            <div class="d-grid gap-3">
                                @forelse ($card->comments as $comment)
                                    <article class="card shadow-sm">
                                        <div class="card-body">
                                            <form method="POST" action="{{ route('todo.comments.update', [$board, $card, $comment]) }}" data-card-modal-form>
                                                @csrf @method('PUT')
                                                <textarea class="form-control mb-2" name="body" rows="3" aria-label="Comment" required>{{ $comment->body }}</textarea>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                                    <span class="small text-body-secondary ms-auto">{{ $comment->updated_at->diffForHumans() }}</span>
                                                </div>
                                            </form>
                                            <form class="mt-2" method="POST" action="{{ route('todo.comments.destroy', [$board, $card, $comment]) }}" data-card-modal-form>
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-link link-danger p-0" type="submit">Delete comment</button>
                                            </form>
                                        </div>
                                    </article>
                                @empty
                                    <p class="text-body-secondary small">No comments yet.</p>
                                @endforelse
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-danger me-auto" type="button" data-delete-card-start>Delete card</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit" form="card-update-{{ $card->id }}">Save card</button>
                </div>
                <div class="alert alert-danger rounded-0 border-start-0 border-end-0 border-bottom-0 mb-0 d-none" data-delete-card-confirmation>
                    <p class="fw-semibold mb-2">Delete this card permanently?</p>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('todo.cards.destroy', [$board, $card]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" type="submit">Yes, delete card</button>
                        </form>
                        <button class="btn btn-outline-secondary" type="button" data-delete-card-cancel>Keep card</button>
                    </div>
                </div>
            </div></div>
        </div>
    @endforeach

    @foreach ($board->columns as $column)
        <div class="modal fade" id="delete-column-{{ $column->id }}" tabindex="-1" aria-labelledby="delete-column-{{ $column->id }}-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="delete-column-{{ $column->id }}-title">Delete {{ $column->name }}?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">
                        This will permanently delete the column and
                        @if ($column->cards->count() === 1)
                            its 1 card.
                        @else
                            its {{ $column->cards->count() }} cards.
                        @endif
                        This action cannot be undone.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('todo.columns.destroy', [$board, $column]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Delete column</button>
                    </form>
                </div>
            </div></div>
        </div>
    @endforeach

    <div class="modal fade" id="edit-board-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('todo.boards.update', $board) }}">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="modal-title fs-5">Edit board</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="edit-board-name">Name</label>
                    <input class="form-control mb-3" id="edit-board-name" name="name" value="{{ $board->name }}" required>
                    <label class="form-label" for="edit-board-description">Description</label>
                    <textarea class="form-control" id="edit-board-description" name="description" rows="3">{{ $board->description }}</textarea>
                    <input type="hidden" name="show_card_count" value="0">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="edit-board-show-card-count" name="show_card_count" value="1" @checked($board->show_card_count)>
                        <label class="form-check-label" for="edit-board-show-card-count">Show card count</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save board</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="add-column-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('todo.columns.store', $board) }}">
                @csrf
                <div class="modal-header"><h2 class="modal-title fs-5">Add column</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body"><label class="form-label" for="new-column-name">Column name</label><input class="form-control" id="new-column-name" name="name" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Add column</button></div>
            </form>
        </div></div>
    </div>

@endsection

@push('styles')
    <style>
        .todo-board { display: flex; align-items: flex-start; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
        .todo-column { flex: 0 0 21rem; padding: .75rem; border: 1px solid #dee2e6; border-radius: .75rem; background: #eef0f3; }
        .todo-column-header { display: flex; align-items: center; gap: .4rem; margin-bottom: .75rem; }
        .todo-column-drag-handle { display: inline-flex; padding: .25rem; border: 0; color: #6c757d; background: transparent; cursor: grab; }
        .todo-column-drag-handle:active { cursor: grabbing; }
        .todo-column-title-wrap { flex: 1; min-width: 0; }
        .todo-column-title { width: 100%; padding: .3rem .45rem; border: 1px solid transparent; border-radius: .375rem; background: transparent; font-weight: 600; text-align: left; cursor: pointer; }
        .todo-column-title-form { display: flex; gap: .35rem; }
        .todo-column-title-form .form-control { min-width: 0; }
        .todo-column-title-form.d-none { display: none !important; }
        .todo-column-ghost { opacity: .4; }
        .todo-card-list { min-height: 3rem; }
        .todo-card { display: flex; align-items: flex-start; margin-bottom: .75rem; padding: .75rem; border: 1px solid #dee2e6; border-radius: .625rem; background: #fff; box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075); cursor: grab; transition: border-color 150ms ease, box-shadow 150ms ease; }
        .todo-card:hover { border-color: #6ea8fe; box-shadow: 0 0 0 .12rem rgba(13, 110, 253, .18); }
        .todo-card-content { flex: 1; min-width: 0; transition: transform 150ms ease; }
        .todo-card-complete, .todo-card-edit-trigger { display: inline-flex; align-items: center; justify-content: center; width: 0; padding: 0; border: 0; opacity: 0; overflow: hidden; color: #6c757d; background: transparent; transform: scale(.8); transition: width 150ms ease, opacity 150ms ease, transform 150ms ease, color 150ms ease; }
        .todo-card:hover .todo-card-complete, .todo-card:focus-within .todo-card-complete, .todo-card.is-complete .todo-card-complete { width: 1.75rem; opacity: 1; transform: scale(1); }
        .todo-card:hover .todo-card-edit-trigger, .todo-card:focus-within .todo-card-edit-trigger { width: 1.75rem; opacity: 1; transform: scale(1); }
        .todo-card:hover .todo-card-content, .todo-card:focus-within .todo-card-content, .todo-card.is-complete .todo-card-content { transform: translateX(.15rem); }
        .todo-card-complete-circle { display: inline-flex; align-items: center; justify-content: center; width: 1.1rem; height: 1.1rem; border: 1.5px solid #6c757d; border-radius: 50%; color: transparent; font-size: .7rem; }
        .todo-card.is-complete .todo-card-complete-circle { color: #fff; border-color: #198754; background: #198754; }
        .todo-card.is-complete .todo-card-content { color: #6c757d; text-decoration: line-through; }
        .todo-card-complete:hover, .todo-card-edit-trigger:hover { color: #0d6efd; }
        .todo-add-card-trigger { width: 100%; padding: .5rem .65rem; border: 0; border-radius: .5rem; color: #495057; background: transparent; text-align: left; transition: background-color 120ms ease; }
        .todo-add-card-trigger:hover, .todo-add-card-trigger:focus-visible { background: rgba(0, 0, 0, .09); }
        .todo-add-card-form { padding-top: .25rem; }
        .todo-add-card-form textarea { resize: none; }
        .todo-add-card-cancel { padding: .1rem .45rem; border: 0; color: #6c757d; background: transparent; font-size: 1.75rem; line-height: 1; }
        .todo-add-card-cancel:hover { color: #212529; }
        .todo-card-ghost { opacity: .35; }
        .todo-card-modal-dialog { width: calc(100vw - 3rem); max-width: 90rem; }
        .todo-card-modal-dialog .modal-content { max-height: calc(100vh - 3rem); }
        .todo-card-comments { min-height: 34rem; }
        .note-editor-surface { min-height: 10rem; padding: .75rem; outline: none; background: #fff; }
        .note-editor-surface > :last-child { margin-bottom: 0; }
        .note-editor-surface mark { background: #fff3a3; }
        .todo-checklist-toggle { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; padding: 0; }
        @media (max-width: 991.98px) {
            .todo-card-modal-dialog { width: auto; margin: .75rem; }
            .todo-card-modal-main { border-right: 0 !important; }
            .todo-card-comments { min-height: 0; border-top: 1px solid var(--bs-border-color); }
        }
    </style>
@endpush

@push('scripts')
    @vite(['resources/js/todo-board.ts', 'resources/js/notes.ts'])
@endpush
