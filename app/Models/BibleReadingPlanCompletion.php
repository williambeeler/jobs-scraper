<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleReadingPlanCompletion extends Model
{
    protected $fillable = ['bible_reading_plan_day_id', 'reading_year', 'completed_at'];

    protected function casts(): array
    {
        return ['reading_year' => 'integer', 'completed_at' => 'immutable_datetime'];
    }
}
