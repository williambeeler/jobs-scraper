<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Card;
use App\Support\NoteHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function store(Request $request, Board $board, BoardColumn $column): RedirectResponse
    {
        abort_unless($column->board_id === $board->id, 404);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $validated['board_id'] = $board->id;
        $validated['position'] = ((int) $column->cards()->max('position')) + 1;
        $column->cards()->create($validated);

        return back()->with('status', 'Card added.');
    }

    public function move(Request $request, Board $board, Card $card): JsonResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        $validated = $request->validate([
            'board_column_id' => ['required', 'integer'],
        ]);
        $column = $board->columns()->findOrFail($validated['board_column_id']);

        $card->update([
            'board_column_id' => $column->id,
            'position' => ((int) $column->cards()->where('id', '!=', $card->id)->max('position')) + 1,
        ]);

        return response()->json(['board_column_id' => $column->id]);
    }

    public function update(Request $request, Board $board, Card $card, NoteHtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        $validated = $request->validate([
            'board_column_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $column = $board->columns()->findOrFail($validated['board_column_id']);
        $validated['description'] = $sanitizer->sanitize($validated['description'] ?? '');
        $validated['position'] = $column->id === $card->board_column_id
            ? $card->position
            : ((int) $column->cards()->max('position')) + 1;
        $card->update($validated);

        return back()->with('status', 'Card updated.');
    }

    public function complete(Board $board, Card $card): JsonResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        $card->update(['completed_at' => $card->completed_at ? null : now()]);

        return response()->json([
            'completed' => $card->completed_at !== null,
            'completed_at' => $card->completed_at?->toIso8601String(),
        ]);
    }

    public function destroy(Board $board, Card $card): RedirectResponse
    {
        abort_unless($card->board_id === $board->id, 404);
        $card->delete();

        return back()->with('status', 'Card deleted.');
    }
}
