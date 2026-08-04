<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerPath extends Model
{
    protected $fillable = ['title', 'description'];

    protected static function booted(): void
    {
        static::deleting(fn (self $careerPath) => $careerPath->files()->get()->each->delete());
    }

    public function agendas(): BelongsToMany
    {
        return $this->belongsToMany(LearningTopic::class)->withTimestamps();
    }

    public function files(): HasMany
    {
        return $this->hasMany(CareerPathFile::class);
    }
}
