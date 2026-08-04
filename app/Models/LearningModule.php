<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LearningModule extends Model
{
    protected $fillable = [
        'title',
        'url',
        'description',
        'completion_date',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'is_completed' => 'boolean',
        ];
    }

    public function moduleable(): MorphTo
    {
        return $this->morphTo();
    }
}
