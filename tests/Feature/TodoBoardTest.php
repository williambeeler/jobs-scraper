<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\CardChecklist;
use App\Models\CardChecklistItem;
use App\Models\CardComment;

class TodoBoardTest extends FeatureTestCase
{
    public function test_a_board_starts_with_the_three_default_columns(): void
    {
        $this->post(route('todo.boards.store'), [
            'name' => 'Website Launch',
            'description' => 'Tasks for launch day.',
            'show_card_count' => true,
        ])->assertRedirect();

        $board = Board::query()->firstOrFail();
        $this->assertSame(['Backlog', 'In Progress', 'Done'], $board->columns()->pluck('name')->all());

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('Website Launch')
            ->assertSeeInOrder(['Backlog', 'In Progress', 'Done'])
            ->assertSee('data-card-list', false);
    }

    public function test_columns_can_be_added_and_renamed_per_board(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Product Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();

        $this->post(route('todo.columns.store', $board), ['name' => 'Review'])->assertRedirect();
        $column = $board->columns()->where('name', 'Review')->firstOrFail();
        $this->put(route('todo.columns.update', [$board, $column]), ['name' => 'QA Review'])->assertRedirect();

        $this->assertDatabaseHas('board_columns', ['board_id' => $board->id, 'name' => 'QA Review']);

        $columns = $board->columns()->get();
        $newOrder = $columns->reverse()->pluck('id')->values()->all();
        $this->patchJson(route('todo.columns.reorder', $board), ['columns' => $newOrder])
            ->assertOk()
            ->assertExactJson(['columns' => $newOrder]);
        $this->assertSame($newOrder, $board->columns()->pluck('id')->all());

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('data-column-drag-handle', false)
            ->assertSee('data-column-title', false)
            ->assertSee('data-reorder-url="'.route('todo.columns.reorder', $board).'"', false);
    }

    public function test_a_column_can_be_deleted_after_confirmation(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Cleanup Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();
        $column = $board->columns()->where('name', 'Backlog')->firstOrFail();
        $this->post(route('todo.cards.store', [$board, $column]), ['title' => 'Remove with column']);
        $card = $board->cards()->firstOrFail();

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('data-column-title-cancel', false)
            ->assertSee('data-bs-target="#delete-column-'.$column->id.'"', false)
            ->assertSee('This action cannot be undone.')
            ->assertSee(route('todo.columns.destroy', [$board, $column]), false);

        $this->post(route('todo.boards.store'), ['name' => 'Another Board', 'show_card_count' => true]);
        $otherBoard = Board::query()->where('name', 'Another Board')->firstOrFail();
        $this->delete(route('todo.columns.destroy', [$otherBoard, $column]))->assertNotFound();

        $this->delete(route('todo.columns.destroy', [$board, $column]))
            ->assertRedirect(route('todo.boards.show', $board));

        $this->assertModelMissing($column);
        $this->assertModelMissing($card);
    }

    public function test_card_counts_can_be_hidden_per_board(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Quiet Board', 'show_card_count' => false]);
        $board = Board::query()->firstOrFail();

        $this->assertFalse($board->show_card_count);
        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertDontSee('data-card-count', false)
            ->assertSee('name="show_card_count"', false);

        $this->put(route('todo.boards.update', $board), [
            'name' => $board->name,
            'description' => null,
            'show_card_count' => true,
        ])->assertRedirect();
        $this->assertTrue($board->fresh()->show_card_count);
    }

    public function test_cards_can_be_created_moved_and_deleted(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Editorial Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();
        $backlog = $board->columns()->where('name', 'Backlog')->firstOrFail();
        $done = $board->columns()->where('name', 'Done')->firstOrFail();

        $this->post(route('todo.cards.store', [$board, $backlog]), [
            'title' => 'Draft article',
            'description' => 'Write the first draft.',
        ])->assertRedirect();
        $card = $board->cards()->firstOrFail();

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('data-card-complete', false)
            ->assertSee(route('todo.cards.complete', [$board, $card]), false)
            ->assertSee('aria-label="Edit Draft article"', false)
            ->assertSee('data-delete-card-start', false)
            ->assertSee('data-delete-card-confirmation', false)
            ->assertDontSee('aria-label="Delete Draft article"', false);

        $this->patchJson(route('todo.cards.complete', [$board, $card]))
            ->assertOk()
            ->assertJson(['completed' => true]);
        $this->assertNotNull($card->fresh()->completed_at);

        $this->patchJson(route('todo.cards.move', [$board, $card]), [
            'board_column_id' => $done->id,
        ])->assertOk()->assertJson(['board_column_id' => $done->id]);
        $this->assertSame($done->id, $card->fresh()->board_column_id);

        $this->delete(route('todo.cards.destroy', [$board, $card]))->assertRedirect();
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_cards_can_be_added_from_a_column_composer(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Support Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();
        $inProgress = $board->columns()->where('name', 'In Progress')->firstOrFail();

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('data-add-card-trigger', false)
            ->assertSee('Add a card')
            ->assertSee('data-add-card-form', false)
            ->assertSee('placeholder="Enter a title or paste a link"', false)
            ->assertSee('data-add-card-cancel', false)
            ->assertDontSee('data-bs-target="#add-card-modal"', false);

        $this->post(route('todo.cards.store', [$board, $inProgress]), [
            'title' => 'Answer customer',
        ])->assertRedirect();

        $this->assertDatabaseHas('cards', [
            'board_id' => $board->id,
            'board_column_id' => $inProgress->id,
            'title' => 'Answer customer',
        ]);
    }

    public function test_an_existing_card_can_be_edited_in_a_modal(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Editing Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();
        $backlog = $board->columns()->where('name', 'Backlog')->firstOrFail();
        $done = $board->columns()->where('name', 'Done')->firstOrFail();
        $this->post(route('todo.cards.store', [$board, $backlog]), ['title' => 'Old title']);
        $card = $board->cards()->firstOrFail();

        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('data-bs-target="#edit-card-'.$card->id.'"', false)
            ->assertSee('id="edit-card-'.$card->id.'"', false)
            ->assertSee('todo-card-modal-dialog', false)
            ->assertSee('data-note-editor', false)
            ->assertSee('form="card-update-'.$card->id.'"', false)
            ->assertSee('Delete card')
            ->assertSee('Column');

        $this->put(route('todo.cards.update', [$board, $card]), [
            'board_column_id' => $done->id,
            'title' => 'New title',
            'description' => '<p><strong>Updated</strong> description</p><script>alert(1)</script>',
        ])->assertRedirect();

        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'board_column_id' => $done->id,
            'title' => 'New title',
            'description' => '<p><strong>Updated</strong> description</p>',
        ]);
    }

    public function test_card_comments_and_checklists_can_be_managed_from_the_modal(): void
    {
        $this->post(route('todo.boards.store'), ['name' => 'Team Board', 'show_card_count' => true]);
        $board = Board::query()->firstOrFail();
        $backlog = $board->columns()->where('name', 'Backlog')->firstOrFail();
        $this->post(route('todo.cards.store', [$board, $backlog]), ['title' => 'Ship feature']);
        $card = $board->cards()->firstOrFail();

        $this->post(route('todo.comments.store', [$board, $card]), ['body' => 'Ready for review.'])
            ->assertRedirect();
        $comment = CardComment::query()->firstOrFail();
        $this->put(route('todo.comments.update', [$board, $card, $comment]), ['body' => 'Review completed.'])
            ->assertRedirect();

        $this->post(route('todo.checklists.store', [$board, $card]), ['name' => 'Launch steps'])
            ->assertRedirect();
        $checklist = CardChecklist::query()->firstOrFail();
        $this->put(route('todo.checklists.update', [$board, $card, $checklist]), ['name' => 'Release steps'])
            ->assertRedirect();
        $this->post(route('todo.checklist-items.store', [$board, $card, $checklist]), ['text' => 'Run tests'])
            ->assertRedirect();
        $item = CardChecklistItem::query()->firstOrFail();
        $this->patch(route('todo.checklist-items.toggle', [$board, $card, $checklist, $item]))
            ->assertRedirect();
        $this->put(route('todo.checklist-items.update', [$board, $card, $checklist, $item]), ['text' => 'Run full test suite'])
            ->assertRedirect();

        $this->assertDatabaseHas('card_comments', ['id' => $comment->id, 'body' => 'Review completed.']);
        $this->assertDatabaseHas('card_checklists', ['id' => $checklist->id, 'name' => 'Release steps']);
        $this->assertDatabaseHas('card_checklist_items', [
            'id' => $item->id,
            'text' => 'Run full test suite',
            'is_complete' => true,
        ]);
        $this->get(route('todo.boards.show', $board))
            ->assertOk()
            ->assertSee('Comments')
            ->assertSee('Review completed.')
            ->assertSee('Release steps')
            ->assertSee('Run full test suite')
            ->assertSee('100%');

        $this->delete(route('todo.checklist-items.destroy', [$board, $card, $checklist, $item]))
            ->assertRedirect();
        $this->delete(route('todo.comments.destroy', [$board, $card, $comment]))
            ->assertRedirect();
        $this->delete(route('todo.checklists.destroy', [$board, $card, $checklist]))
            ->assertRedirect();

        $this->assertDatabaseMissing('card_checklist_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('card_comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('card_checklists', ['id' => $checklist->id]);
    }
}
