<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_reading_chapters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('reading_year');
            $table->string('book_slug', 50);
            $table->unsignedSmallInteger('chapter_number');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['reading_year', 'book_slug', 'chapter_number'], 'bible_reading_year_book_chapter_unique');
            $table->index(['reading_year', 'book_slug'], 'bible_reading_year_book_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_reading_chapters');
    }
};
