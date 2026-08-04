<?php

namespace Tests\Feature;

use App\Models\Deck;
use App\Models\FlashCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_deck_crud(): void
    {
        $this->post(route('learning.decks.store'), ['title' => 'PHP', 'description' => 'PHP study cards'])->assertRedirect();
        $deck = Deck::query()->firstOrFail();
        $this->get(route('learning.decks.show', $deck))->assertOk()->assertSee('PHP study cards');
        $this->put(route('learning.decks.update', $deck), ['title' => 'Modern PHP'])->assertRedirect(route('learning.decks.show', $deck));
        $this->assertDatabaseHas('decks', ['id' => $deck->id, 'title' => 'Modern PHP']);
        $this->delete(route('learning.decks.destroy', $deck))->assertRedirect(route('learning.decks.index'));
        $this->assertDatabaseMissing('decks', ['id' => $deck->id]);
    }

    public function test_flash_card_crud_and_difficulty(): void
    {
        $deck = Deck::query()->create(['title' => 'Laravel']);
        $this->post(route('learning.decks.cards.store', $deck), [
            'question' => 'What is route model binding?',
            'answer' => 'Automatic model resolution from route parameters.',
            'difficulty' => 'hard',
        ])->assertRedirect(route('learning.decks.show', $deck));
        $card = FlashCard::query()->firstOrFail();
        $this->get(route('learning.cards.show', $card))->assertOk()->assertSee('What is route model binding?')->assertSee('Hard');
        $this->put(route('learning.cards.update', $card), [
            'question' => 'What is implicit binding?',
            'answer' => 'Laravel resolves the model automatically.',
            'difficulty' => 'easy',
        ])->assertRedirect(route('learning.cards.show', $card));
        $this->assertDatabaseHas('flash_cards', ['id' => $card->id, 'difficulty' => 'easy']);
        $this->delete(route('learning.cards.destroy', $card))->assertRedirect(route('learning.decks.show', $deck));
    }

    public function test_flash_card_fields_are_validated(): void
    {
        $deck = Deck::query()->create(['title' => 'Testing']);
        $this->post(route('learning.decks.cards.store', $deck), [
            'question' => '',
            'answer' => '',
            'difficulty' => 'impossible',
        ])->assertSessionHasErrors(['question', 'answer', 'difficulty']);
    }

    public function test_deck_deletion_cascades_cards(): void
    {
        $deck = Deck::query()->create(['title' => 'Temporary']);
        $card = $deck->flashCards()->create(['question' => 'Q', 'answer' => 'A', 'difficulty' => 'medium']);
        $deck->delete();
        $this->assertDatabaseMissing('flash_cards', ['id' => $card->id]);
    }
}
