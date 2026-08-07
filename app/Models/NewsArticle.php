<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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

    public function publishedLabel(): ?string
    {
        if ($this->published_at === null) {
            return null;
        }

        $timezone = (string) config('app.display_timezone', 'America/Chicago');
        $now = Carbon::now($timezone);
        $publishedAt = $this->published_at->copy()->timezone($timezone);
        $cacheIdentity = $this->getKey() ?? $this->guid_hash;
        $cacheKey = implode(':', [
            'news-article-published-label',
            $cacheIdentity,
            $publishedAt->timestamp,
            $now->format('YmdH'),
        ]);

        return Cache::remember($cacheKey, $now->copy()->endOfHour(), function () use ($now, $publishedAt): string {
            $ageInSeconds = max(0, $publishedAt->diffInSeconds($now, false));

            if ($ageInSeconds >= 86400) {
                return $publishedAt->format('M j, Y');
            }

            $hours = (int) floor($ageInSeconds / 3600);

            return $hours < 1
                ? 'Less than an hour ago'
                : trans_choice(':count hour ago|:count hours ago', $hours, ['count' => $hours]);
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
