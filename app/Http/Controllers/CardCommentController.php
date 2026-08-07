<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Card;
use App\Models\CardComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CardCommentController extends Controller
{
    public function store(Request $request, Board $board, Card $card): RedirectResponse
    {
        $this->authorizeCard($board, $card);
        $card->comments()->create($request->validate(['body' => ['required', 'string', 'max:5000']]));

        return back()->with('status', 'Comment added.');
    }

    public function update(Request $request, Board $board, Card $card, CardComment $comment): RedirectResponse
    {
        $this->authorizeCard($board, $card, $comment->card_id);
        $comment->update($request->validate(['body' => ['required', 'string', 'max:5000']]));

        return back()->with('status', 'Comment updated.');
    }

    public function destroy(Board $board, Card $card, CardComment $comment): RedirectResponse
    {
        $this->authorizeCard($board, $card, $comment->card_id);
        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }

    private function authorizeCard(Board $board, Card $card, ?int $relatedCardId = null): void
    {
        abort_unless($card->board_id === $board->id && ($relatedCardId === null || $relatedCardId === $card->id), 404);
    }
}
