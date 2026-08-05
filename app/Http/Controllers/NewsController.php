<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NewsFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'feed' => ['nullable', 'integer', 'exists:news_feeds,id'],
        ]);

        $articles = NewsArticle::query()
            ->with('feed')
            ->when($filters['feed'] ?? null, fn ($query, int $feedId) => $query->where('news_feed_id', $feedId))
            ->orderByRaw('published_at IS NULL')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('news.index', [
            'articles' => $articles,
            'feeds' => NewsFeed::query()->orderBy('name')->get(),
            'selectedFeedId' => $filters['feed'] ?? null,
        ]);
    }
}
