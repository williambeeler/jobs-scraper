<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsFeed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class NewsReaderTest extends FeatureTestCase
{
    public function test_the_scheduled_news_refresh_imports_active_feeds(): void
    {
        Http::fake([
            'https://example.com/scheduled.xml' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Scheduled News</title>
                        <link>https://example.com</link>
                        <item>
                            <guid>scheduled-article-1</guid>
                            <title>Scheduled story</title>
                            <link>https://example.com/stories/scheduled</link>
                            <pubDate>Fri, 07 Aug 2026 14:30:00 GMT</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
        ]);

        NewsFeed::query()->create([
            'name' => 'Scheduled News',
            'url' => 'https://example.com/scheduled.xml',
            'url_hash' => hash('sha256', 'https://example.com/scheduled.xml'),
            'icon_url' => 'https://example.com/favicon.ico',
            'is_active' => true,
        ]);
        NewsFeed::query()->create([
            'name' => 'Inactive News',
            'url' => 'https://example.com/inactive.xml',
            'url_hash' => hash('sha256', 'https://example.com/inactive.xml'),
            'is_active' => false,
        ]);

        $this->assertSame(0, Artisan::call('news:refresh-feeds'));

        $this->assertDatabaseHas('news_articles', [
            'title' => 'Scheduled story',
            'url' => 'https://example.com/stories/scheduled',
        ]);
        Http::assertNotSent(fn ($request): bool => $request->url() === 'https://example.com/inactive.xml');
    }

    public function test_article_publication_labels_are_hourly_then_switch_to_the_published_date(): void
    {
        $feed = NewsFeed::query()->create([
            'name' => 'Example News',
            'url' => 'https://example.com/feed.xml',
            'url_hash' => hash('sha256', 'https://example.com/feed.xml'),
        ]);
        $article = NewsArticle::query()->create([
            'news_feed_id' => $feed->id,
            'guid_hash' => hash('sha256', 'hourly-label'),
            'title' => 'Hourly label article',
            'url' => 'https://example.com/hourly-label',
            'published_at' => '2026-08-07 10:00:00',
        ]);

        Carbon::setTestNow('2026-08-07 15:30:00 UTC');
        $this->assertSame('5 hours ago', $article->publishedLabel());

        Carbon::setTestNow('2026-08-08 15:30:00 UTC');
        $this->assertSame('Aug 7, 2026', $article->publishedLabel());
    }

    public function test_an_rss_feed_can_be_added_and_imported(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <rss version="2.0">
                    <channel>
                        <title>Example News</title>
                        <link>https://example.com</link>
                        <item>
                            <guid>article-1</guid>
                            <title>Published story</title>
                            <link>https://example.com/stories/published</link>
                            <description><![CDATA[<p><img src="https://cdn.example.com/story.jpg">A useful summary.</p>]]></description>
                            <pubDate>Tue, 04 Aug 2026 14:30:00 GMT</pubDate>
                        </item>
                    </channel>
                </rss>
                XML),
            'https://example.com' => Http::response(
                '<html><head><link rel="icon" href="/favicon.png"></head></html>',
            ),
        ]);

        $this->post(route('news.feeds.store'), [
            'url' => 'https://example.com/feed.xml',
        ])->assertRedirect(route('news.feeds.index'));

        $this->assertDatabaseHas('news_feeds', [
            'name' => 'Example News',
            'url' => 'https://example.com/feed.xml',
            'last_error' => null,
            'icon_url' => 'https://example.com/favicon.png',
        ]);
        $this->assertDatabaseHas('news_articles', [
            'title' => 'Published story',
            'url' => 'https://example.com/stories/published',
            'image_url' => 'https://cdn.example.com/story.jpg',
            'summary' => 'A useful summary.',
            'published_at' => '2026-08-04 14:30:00',
        ]);
    }

    public function test_atom_feeds_are_supported_and_existing_entries_are_updated(): void
    {
        Http::fake([
            'https://example.com/atom.xml' => Http::response(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <feed xmlns="http://www.w3.org/2005/Atom">
                    <title>Example Atom</title>
                    <link href="https://example.com" rel="alternate" />
                    <entry>
                        <id>tag:example.com,2026:42</id>
                        <title>Atom story</title>
                        <link href="https://example.com/stories/atom" rel="alternate" />
                        <author><name>Pat Writer</name></author>
                        <summary>Atom summary</summary>
                        <published>2026-08-03T09:15:00Z</published>
                    </entry>
                </feed>
                XML),
        ]);

        $this->post(route('news.feeds.store'), [
            'url' => 'https://example.com/atom.xml',
        ])->assertRedirect(route('news.feeds.index'));

        $feed = NewsFeed::query()->firstOrFail();

        $this->post(route('news.feeds.refresh', $feed))->assertRedirect();

        $this->assertSame(1, NewsArticle::query()->count());
        $this->assertDatabaseHas('news_articles', [
            'title' => 'Atom story',
            'author' => 'Pat Writer',
            'published_at' => '2026-08-03 09:15:00',
        ]);
    }

    public function test_news_is_ordered_by_article_publication_date_not_import_date(): void
    {
        $feed = NewsFeed::query()->create([
            'name' => 'Ordering Feed',
            'url' => 'https://example.com/ordering.xml',
            'url_hash' => hash('sha256', 'https://example.com/ordering.xml'),
            'icon_url' => 'https://example.com/favicon.ico',
        ]);

        $older = NewsArticle::query()->create([
            'news_feed_id' => $feed->id,
            'guid_hash' => hash('sha256', 'older'),
            'title' => 'Older published article',
            'url' => 'https://example.com/older',
            'published_at' => '2026-07-01 12:00:00',
        ]);
        $older->forceFill(['created_at' => now(), 'updated_at' => now()])->save();

        $newer = NewsArticle::query()->create([
            'news_feed_id' => $feed->id,
            'guid_hash' => hash('sha256', 'newer'),
            'title' => 'Newer published article',
            'url' => 'https://example.com/newer',
            'image_url' => 'https://cdn.example.com/newer.jpg',
            'published_at' => '2026-08-01 12:00:00',
        ]);
        $newer->forceFill(['created_at' => now()->subMonth(), 'updated_at' => now()->subMonth()])->save();

        $this->get(route('news'))
            ->assertOk()
            ->assertSee('Articles are organized by their published date.')
            ->assertSeeInOrder(['Newer published article', 'Older published article'])
            ->assertSee('src="https://cdn.example.com/newer.jpg"', false)
            ->assertSee('src="https://example.com/favicon.ico"', false)
            ->assertSee('width="100"', false)
            ->assertSee('height="100"', false);
    }

    public function test_a_duplicate_feed_url_is_rejected(): void
    {
        $url = 'https://example.com/duplicate.xml';
        NewsFeed::query()->create([
            'name' => 'Existing Feed',
            'url' => $url,
            'url_hash' => hash('sha256', $url),
        ]);

        $this->post(route('news.feeds.store'), ['url' => $url])
            ->assertSessionHasErrors('url');

        $this->assertSame(1, NewsFeed::query()->count());
    }
}
