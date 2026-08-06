<?php

namespace Tests\Feature;

use App\Enums\BibleTranslation;
use App\Models\BibleReadingPlan;
use App\Support\BibleBooks;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BibleReadingTest extends FeatureTestCase
{
    public function test_bible_dashboard_displays_only_the_current_year_worksheet_summary(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        $this->withoutVite();

        $this->get(route('bible'))
            ->assertOk()
            ->assertViewIs('bible.index')
            ->assertViewHas('displayYear', 2026)
            ->assertSee('Bible Study Worksheets')
            ->assertSee('Search Scripture')
            ->assertSee('action="'.route('bible.search').'"', false)
            ->assertSee('<option value="net" selected>NET</option>', false)
            ->assertSee('2026')
            ->assertSee(route('bible.worksheets'), false)
            ->assertDontSee('Old Testament')
            ->assertDontSee('data-bible-reading-page', false);
    }

    public function test_worksheets_page_displays_the_reading_tracker_for_the_selected_year(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        $this->withoutVite();

        $this->get(route('bible.worksheets', ['year' => 2025]))
            ->assertOk()
            ->assertViewIs('bible.reading.index')
            ->assertViewHas('displayYear', 2025)
            ->assertViewHas('totalChaptersCount', BibleBooks::totalChapters())
            ->assertSee('Old Testament')
            ->assertSee('New Testament')
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('bible.worksheets').'"', false)
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

    public function test_net_is_the_default_translation(): void
    {
        $this->assertSame(BibleTranslation::NET, BibleTranslation::default());
        $this->assertSame('bible_translation_net', BibleTranslation::default()->table());
    }

    public function test_completing_a_partial_passage_marks_every_touched_chapter(): void
    {
        Carbon::setTestNow('2026-08-06 12:00:00');
        $plan = BibleReadingPlan::query()->create(['name' => 'Test plan', 'slug' => 'test-plan', 'duration_years' => 1]);
        $day = $plan->days()->create(['cycle_year' => 1, 'month' => 8, 'month_day' => 6]);
        $day->passages()->create([
            'book_id' => 43,
            'book_slug' => 'john',
            'start_chapter' => 3,
            'start_verse' => 16,
            'end_chapter' => 4,
            'end_verse' => 2,
            'reference' => 'John 3:16-4:2',
            'display_order' => 1,
        ]);

        $this->post(route('bible.plans.complete', $day), ['plan' => 'test-plan', 'cycle' => 1, 'translation' => 'net'])
            ->assertRedirect(route('bible.plans.index', ['translation' => 'net', 'plan' => 'test-plan', 'cycle' => 1]));

        $this->assertDatabaseHas('bible_reading_chapters', ['reading_year' => 2026, 'book_slug' => 'john', 'chapter_number' => 3]);
        $this->assertDatabaseHas('bible_reading_chapters', ['reading_year' => 2026, 'book_slug' => 'john', 'chapter_number' => 4]);
        $this->assertDatabaseCount('bible_reading_plan_completions', 1);
    }

    public function test_two_year_plan_reader_uses_selected_cycle_and_defaults_to_net(): void
    {
        Carbon::setTestNow('2026-08-06 12:00:00');
        $this->withoutVite();
        Schema::create('bible_translation_net', function ($table): void {
            $table->unsignedTinyInteger('book_id');
            $table->string('book');
            $table->unsignedTinyInteger('chapter');
            $table->unsignedSmallInteger('verse');
            $table->text('text');
        });
        DB::table('bible_translation_net')->insert(['book_id' => 19, 'book' => 'Psalm', 'chapter' => 1, 'verse' => 1, 'text' => 'Test verse']);
        $plan = BibleReadingPlan::query()->create(['name' => 'Two year plan', 'slug' => 'two-year', 'duration_years' => 2]);
        $day = $plan->days()->create(['cycle_year' => 2, 'month' => 8, 'month_day' => 6]);
        $day->passages()->create(['book_id' => 19, 'book_slug' => 'psalms', 'start_chapter' => 1, 'end_chapter' => 1, 'reference' => 'Psalm 1', 'display_order' => 1]);

        $this->get(route('bible.plans.index', ['plan' => 'two-year', 'cycle' => 2]))
            ->assertOk()
            ->assertViewHas('translation', BibleTranslation::NET)
            ->assertViewHas('cycleYear', 2)
            ->assertSee('Year 2')
            ->assertSee('Test verse');
    }

    public function test_bible_search_defaults_to_net_and_finds_text_and_references(): void
    {
        $this->withoutVite();
        Schema::create('bible_translation_net', function ($table): void {
            $table->unsignedTinyInteger('book_id');
            $table->string('book');
            $table->unsignedTinyInteger('chapter');
            $table->unsignedSmallInteger('verse');
            $table->text('text');
        });
        DB::table('bible_translation_net')->insert([
            ['book_id' => 43, 'book' => 'John', 'chapter' => 3, 'verse' => 16, 'text' => 'For God so loved the world.'],
            ['book_id' => 43, 'book' => 'John', 'chapter' => 3, 'verse' => 17, 'text' => 'God did not send his Son to condemn the world.'],
            ['book_id' => 43, 'book' => 'John', 'chapter' => 4, 'verse' => 1, 'text' => 'Jesus learned that the Pharisees had heard.'],
        ]);

        $this->get(route('bible.search', ['q' => 'John 3:16']))
            ->assertOk()
            ->assertViewHas('translation', BibleTranslation::NET)
            ->assertViewHas('searchMode', 'reference')
            ->assertSee('For God so loved the world.')
            ->assertDontSee('God did not send his Son');

        $this->get(route('bible.search', ['q' => 'world', 'translation' => 'net']))
            ->assertOk()
            ->assertViewHas('searchMode', 'text')
            ->assertSee('2 verses found')
            ->assertSee('For God so loved the world.')
            ->assertSee('God did not send his Son');
    }
}
