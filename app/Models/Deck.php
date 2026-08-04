<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deck extends Model
{
    protected $fillable = ['title', 'description'];

    public function flashCards(): HasMany
    {
        return $this->hasMany(FlashCard::class)->orderBy('created_at');
    }
}
