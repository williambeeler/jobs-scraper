<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\CardChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardChecklistController extends Controller
{
    public function store(Request $request, Board $board, Card $card): RedirectResponse
    {
        $this->authorizeChecklist($board, $card);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $validated['position'] = ((int) $card->checklists()->max('position')) + 1;
        $card->checklists()->create($validated);

        return back()->with('status', 'Checklist added.');
    }

    public function update(Request $request, Board $board, Card $card, CardChecklist $checklist): RedirectResponse
    {
        $this->authorizeChecklist($board, $card, $checklist);
        $checklist->update($request->validate(['name' => ['required', 'string', 'max:255']]));

        return back()->with('status', 'Checklist updated.');
    }

    public function destroy(Board $board, Card $card, CardChecklist $checklist): RedirectResponse
    {
        $this->authorizeChecklist($board, $card, $checklist);
        $checklist->delete();

        return back()->with('status', 'Checklist deleted.');
    }

    private function authorizeChecklist(Board $board, Card $card, ?CardChecklist $checklist = null): void
    {
        abort_unless($card->board_id === $board->id && ($checklist === null || $checklist->card_id === $card->id), 404);
    }
}
