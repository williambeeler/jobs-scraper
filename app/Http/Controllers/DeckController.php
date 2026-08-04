<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\FlashCard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeckController extends Controller
{
    public function index(): View
    {
        return view('learning.decks.index', [
            'decks' => Deck::query()->withCount('flashCards')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('learning.decks.create', ['deck' => new Deck]);
    }

    public function store(Request $request): RedirectResponse
    {
        $deck = Deck::query()->create($this->validated($request));

        return to_route('learning.decks.show', $deck)->with('status', 'Deck added.');
    }

    public function show(Deck $deck): View
    {
        return view('learning.decks.show', [
            'deck' => $deck->load('flashCards'),
            'difficulties' => FlashCard::DIFFICULTIES,
        ]);
    }

    public function edit(Deck $deck): View
    {
        return view('learning.decks.edit', compact('deck'));
    }

    public function update(Request $request, Deck $deck): RedirectResponse
    {
        $deck->update($this->validated($request));

        return to_route('learning.decks.show', $deck)->with('status', 'Deck updated.');
    }

    public function destroy(Deck $deck): RedirectResponse
    {
        $deck->delete();

        return to_route('learning.decks.index')->with('status', 'Deck deleted.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $validated['title'] = trim($validated['title']);
        $validated['description'] = filled($validated['description'] ?? null)
            ? trim($validated['description'])
            : null;

        return $validated;
    }
}
