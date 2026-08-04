<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'learning_topic_id',
        'title',
        'url',
        'description',
        'study_tips',
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

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(LearningTopic::class, 'learning_topic_id');
    }
}
