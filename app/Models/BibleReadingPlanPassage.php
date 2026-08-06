<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleReadingPlanPassage extends Model
{
    protected $fillable = ['book_id', 'book_slug', 'start_chapter', 'start_verse', 'end_chapter', 'end_verse', 'reference', 'display_order'];

    public function day(): BelongsTo
    {
        return $this->belongsTo(BibleReadingPlanDay::class, 'bible_reading_plan_day_id');
    }
}
