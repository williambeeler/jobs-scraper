<article class="card shadow-sm news-article">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            @if ($article->image_url || $article->feed->icon_url)
                <img
                    @class(['news-article-image', 'is-feed-icon' => ! $article->image_url])
                    src="{{ $article->image_url ?: $article->feed->icon_url }}"
                    alt=""
                    width="100"
                    height="100"
                    loading="lazy"
                >
            @endif

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div>
                        <h3 class="h5 mb-1">
                            <a href="{{ $article->url }}" target="_blank" rel="noopener noreferrer">
                                {{ $article->title }}
                            </a>
                        </h3>
                        <div class="small text-secondary mb-2">
                            {{ $article->feed->name }}
                            @if ($article->author)
                                <span aria-hidden="true">&middot;</span>
                                {{ $article->author }}
                            @endif
                        </div>
                    </div>

                    @if ($article->published_at)
                        <time
                            class="small text-secondary text-nowrap"
                            datetime="{{ $article->published_at->toIso8601String() }}"
                            title="Published {{ $article->published_at->timezone(config('app.display_timezone'))->format('F j, Y \a\t g:i A') }}"
                        >
                            {{ $article->publishedLabel() }}
                        </time>
                    @endif
                </div>

                @if ($article->summary)
                    <p class="mb-0 text-body-secondary">{{ str($article->summary)->limit(350) }}</p>
                @endif
            </div>
        </div>
    </div>
</article>
