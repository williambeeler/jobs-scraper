<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PortfolioProject extends Model
{
    protected $fillable = [
        'learning_topic_id',
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

    protected static function booted(): void
    {
        static::deleting(fn (self $project) => $project->learningModules()->delete());
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(LearningTopic::class, 'learning_topic_id');
    }

    public function learningModules(): MorphMany
    {
        return $this->morphMany(LearningModule::class, 'moduleable')->orderBy('title');
    }

    public function progressPercentage(): int
    {
        $total = $this->learningModules->count();

        return $total === 0
            ? 0
            : (int) round(($this->learningModules->where('is_completed', true)->count() / $total) * 100);
    }
}
