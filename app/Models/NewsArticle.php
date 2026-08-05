<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticle extends Model
{
    protected $fillable = [
        'news_feed_id',
        'guid_hash',
        'title',
        'url',
        'image_url',
        'author',
        'summary',
        'published_at',
    ];

    /** @return BelongsTo<NewsFeed, $this> */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(NewsFeed::class, 'news_feed_id');
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
