<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardChecklistItem extends Model
{
    protected $fillable = ['text', 'is_complete', 'position'];

    protected function casts(): array
    {
        return ['is_complete' => 'boolean'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(CardChecklist::class, 'card_checklist_id');
    }
}
