<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function index(): View
    {
        return view('todo.index', [
            'boards' => Board::query()->withCount('cards')->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'show_card_count' => ['required', 'boolean'],
        ]);

        $board = DB::transaction(function () use ($validated): Board {
            $board = Board::query()->create($validated);

            foreach (['Backlog', 'In Progress', 'Done'] as $position => $name) {
                $board->columns()->create(['name' => $name, 'position' => $position]);
            }

            return $board;
        });

        return redirect()->route('todo.boards.show', $board)->with('status', 'Board created.');
    }

    public function show(Board $board): View
    {
        return view('todo.show', [
            'board' => $board->load(['columns.cards', 'cards.comments', 'cards.checklists.items']),
        ]);
    }

    public function update(Request $request, Board $board): RedirectResponse
    {
        $board->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'show_card_count' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'Board updated.');
    }
}
