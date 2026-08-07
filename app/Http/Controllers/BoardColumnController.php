<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardColumnController extends Controller
{
    public function store(Request $request, Board $board): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $validated['position'] = ((int) $board->columns()->max('position')) + 1;
        $board->columns()->create($validated);

        return back()->with('status', 'Column added.');
    }

    public function update(Request $request, Board $board, BoardColumn $column): RedirectResponse
    {
        abort_unless($column->board_id === $board->id, 404);
        $column->update($request->validate(['name' => ['required', 'string', 'max:100']]));

        return back()->with('status', 'Column renamed.');
    }

    public function destroy(Board $board, BoardColumn $column): RedirectResponse
    {
        abort_unless($column->board_id === $board->id, 404);
        $column->delete();

        return redirect()->route('todo.boards.show', $board)->with('status', 'Column deleted.');
    }

    public function reorder(Request $request, Board $board): JsonResponse
    {
        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*' => ['required', 'integer', 'distinct'],
        ]);
        $columnIds = $board->columns()->pluck('id')->all();
        abort_unless(count($columnIds) === count($validated['columns'])
            && collect($columnIds)->sort()->values()->all() === collect($validated['columns'])->sort()->values()->all(), 422);

        DB::transaction(function () use ($board, $validated): void {
            $temporaryOffset = count($validated['columns']) + 1000;
            $board->columns()->increment('position', $temporaryOffset);

            foreach ($validated['columns'] as $position => $columnId) {
                $board->columns()->whereKey($columnId)->update(['position' => $position]);
            }
        });

        return response()->json(['columns' => $validated['columns']]);
    }
}
