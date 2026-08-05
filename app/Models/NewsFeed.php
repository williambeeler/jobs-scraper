<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsFeed extends Model
{
    protected $fillable = [
        'name',
        'url',
        'url_hash',
        'site_url',
        'icon_url',
        'is_active',
        'last_fetched_at',
        'last_error',
    ];

    /** @return HasMany<NewsArticle, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }
}
