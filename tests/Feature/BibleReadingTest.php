<?php

namespace Tests\Feature;

use App\Support\BibleBooks;
use Illuminate\Support\Carbon;

class BibleReadingTest extends FeatureTestCase
{
    public function test_bible_page_displays_the_reading_tracker_for_the_selected_year(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        $this->withoutVite();

        $this->get(route('bible', ['year' => 2025]))
            ->assertOk()
            ->assertViewIs('bible.reading.index')
            ->assertViewHas('displayYear', 2025)
            ->assertViewHas('totalChaptersCount', BibleBooks::totalChapters())
            ->assertSee('Old Testament')
            ->assertSee('New Testament')
            ->assertSee('aria-current="page"', false)
            ->assertSee('data-bible-reading-page', false);
    }

    public function test_a_chapter_can_be_marked_read_and_unread(): void
    {
        $url = route('bible.reading.chapters.update', [
            'year' => 2026,
            'book' => 'john',
            'chapter' => 3,
        ]);

        $this->putJson($url, ['read' => true])
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed_chapters_count', 1);

        $this->assertDatabaseHas('bible_reading_chapters', [
            'reading_year' => 2026,
            'book_slug' => 'john',
            'chapter_number' => 3,
        ]);

        $this->putJson($url, ['read' => false])
            ->assertOk()
            ->assertJsonPath('completed_chapters_count', 0);

        $this->assertDatabaseMissing('bible_reading_chapters', [
            'reading_year' => 2026,
            'book_slug' => 'john',
            'chapter_number' => 3,
        ]);
    }

    public function test_an_invalid_book_or_chapter_is_rejected(): void
    {
        $this->putJson('/bible/2026/books/not-a-book/chapters/1', ['read' => true])
            ->assertNotFound();

        $this->putJson('/bible/2026/books/jude/chapters/2', ['read' => true])
            ->assertNotFound();
    }
}
