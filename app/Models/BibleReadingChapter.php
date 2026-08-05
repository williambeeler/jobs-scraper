<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleReadingChapter extends Model
{
    protected $table = 'bible_reading_chapters';

    protected $fillable = [
        'reading_year',
        'book_slug',
        'chapter_number',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'reading_year' => 'integer',
            'chapter_number' => 'integer',
            'read_at' => 'immutable_datetime',
        ];
    }
}
