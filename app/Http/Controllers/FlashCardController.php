<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\FlashCard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FlashCardController extends Controller
{
    public function create(Deck $deck): View
    {
        return view('learning.cards.create', [
            'deck' => $deck,
            'flashCard' => new FlashCard(['difficulty' => 'medium']),
            'difficulties' => FlashCard::DIFFICULTIES,
        ]);
    }

    public function store(Request $request, Deck $deck): RedirectResponse
    {
        $deck->flashCards()->create($this->validated($request));

        return to_route('learning.decks.show', $deck)->with('status', 'Flash card added.');
    }

    public function show(FlashCard $flashCard): View
    {
        return view('learning.cards.show', [
            'flashCard' => $flashCard->load('deck'),
            'difficulties' => FlashCard::DIFFICULTIES,
        ]);
    }

    public function edit(FlashCard $flashCard): View
    {
        return view('learning.cards.edit', [
            'flashCard' => $flashCard->load('deck'),
            'deck' => $flashCard->deck,
            'difficulties' => FlashCard::DIFFICULTIES,
        ]);
    }

    public function update(Request $request, FlashCard $flashCard): RedirectResponse
    {
        $flashCard->update($this->validated($request));

        return to_route('learning.cards.show', $flashCard)->with('status', 'Flash card updated.');
    }

    public function destroy(FlashCard $flashCard): RedirectResponse
    {
        $deck = $flashCard->deck;
        $flashCard->delete();

        return to_route('learning.decks.show', $deck)->with('status', 'Flash card deleted.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'difficulty' => ['required', Rule::in(array_keys(FlashCard::DIFFICULTIES))],
        ]);
        $validated['question'] = trim($validated['question']);
        $validated['answer'] = trim($validated['answer']);

        return $validated;
    }
}
