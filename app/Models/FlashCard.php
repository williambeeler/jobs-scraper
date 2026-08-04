<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashCard extends Model
{
    public const DIFFICULTIES = [
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
    ];

    protected $fillable = ['deck_id', 'question', 'answer', 'difficulty'];

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }
}
