<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleReadingPlan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'duration_years'];

    public function days(): HasMany
    {
        return $this->hasMany(BibleReadingPlanDay::class);
    }
}
