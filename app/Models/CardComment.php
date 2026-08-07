<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardComment extends Model
{
    protected $fillable = ['body'];

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
