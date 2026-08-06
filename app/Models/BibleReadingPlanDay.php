<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleReadingPlanDay extends Model
{
    protected $fillable = ['cycle_year', 'month', 'month_day', 'day_number', 'is_catch_up'];

    protected function casts(): array
    {
        return ['cycle_year' => 'integer', 'month' => 'integer', 'month_day' => 'integer', 'day_number' => 'integer', 'is_catch_up' => 'boolean'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BibleReadingPlan::class, 'bible_reading_plan_id');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(BibleReadingPlanPassage::class)->orderBy('display_order');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(BibleReadingPlanCompletion::class);
    }
}
