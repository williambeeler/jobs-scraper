<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\CardChecklist;
use App\Models\CardChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardChecklistItemController extends Controller
{
    public function store(Request $request, Board $board, Card $card, CardChecklist $checklist): RedirectResponse
    {
        $this->authorizeItem($board, $card, $checklist);
        $validated = $request->validate(['text' => ['required', 'string', 'max:255']]);
        $validated['position'] = ((int) $checklist->items()->max('position')) + 1;
        $checklist->items()->create($validated);

        return back()->with('status', 'Checklist item added.');
    }

    public function update(Request $request, Board $board, Card $card, CardChecklist $checklist, CardChecklistItem $item): RedirectResponse
    {
        $this->authorizeItem($board, $card, $checklist, $item);
        $item->update($request->validate(['text' => ['required', 'string', 'max:255']]));

        return back()->with('status', 'Checklist item updated.');
    }

    public function toggle(Board $board, Card $card, CardChecklist $checklist, CardChecklistItem $item): RedirectResponse
    {
        $this->authorizeItem($board, $card, $checklist, $item);
        $item->update(['is_complete' => ! $item->is_complete]);

        return back()->with('status', 'Checklist item updated.');
    }

    public function destroy(Board $board, Card $card, CardChecklist $checklist, CardChecklistItem $item): RedirectResponse
    {
        $this->authorizeItem($board, $card, $checklist, $item);
        $item->delete();

        return back()->with('status', 'Checklist item deleted.');
    }

    private function authorizeItem(Board $board, Card $card, CardChecklist $checklist, ?CardChecklistItem $item = null): void
    {
        abort_unless($card->board_id === $board->id
            && $checklist->card_id === $card->id
            && ($item === null || $item->card_checklist_id === $checklist->id), 404);
    }
}
