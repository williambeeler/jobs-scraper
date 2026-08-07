<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsFeed;
use Illuminate\Support\Carbon;

class DashboardTest extends FeatureTestCase
{
    public function test_dashboard_shows_a_time_appropriate_greeting_and_the_ten_newest_jobs(): void
    {
        Carbon::setTestNow('2026-08-04 14:00:00 UTC');

        foreach (range(1, 11) as $number) {
            $seenAt = Carbon::parse('2026-08-04 15:00:00 UTC')->addMinutes($number);
            $this->createListing(
                title: "Dashboard job {$number}",
                url: "https://example.com/jobs/dashboard-{$number}",
                lastSeenAt: $seenAt,
                createdAt: $seenAt,
            );
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('Good morning, William!')
            ->assertSee('Dashboard job 11')
            ->assertSee('Dashboard job 2')
            ->assertDontSee('href="https://example.com/jobs/dashboard-1"', false)
            ->assertSeeInOrder(['Dashboard job 11', 'Dashboard job 10', 'Dashboard job 9'])
            ->assertSee('Not Interested')
            ->assertSee('Applied')
            ->assertSee('data-company-preference-form', false)
            ->assertSee('data-job-status-form', false)
            ->assertSee('dashboard-jobs-', false)
            ->assertViewHas('listings', fn ($listings) => $listings->count() === 10);
    }

    public function test_dashboard_uses_an_afternoon_greeting(): void
    {
        Carbon::setTestNow('2026-08-04 20:00:00 UTC');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Good afternoon, William!');
    }

    public function test_dashboard_shows_the_latest_fifteen_rss_articles(): void
    {
        $feed = NewsFeed::query()->create([
            'name' => 'Example News',
            'url' => 'https://example.com/feed.xml',
            'url_hash' => hash('sha256', 'https://example.com/feed.xml'),
            'icon_url' => 'https://example.com/feed-icon.png',
            'is_active' => true,
        ]);

        foreach (range(1, 16) as $number) {
            NewsArticle::query()->create([
                'news_feed_id' => $feed->id,
                'guid_hash' => hash('sha256', "article-{$number}"),
                'title' => "Article {$number}",
                'url' => "https://example.com/articles/{$number}",
                'image_url' => $number === 16 ? 'https://example.com/article-16.jpg' : null,
                'author' => $number === 16 ? 'Example Author' : null,
                'summary' => $number === 16 ? 'A normalized article summary.' : null,
                'published_at' => Carbon::parse('2026-08-05 12:00:00')->addMinutes($number),
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee("Today's News", false)
            ->assertSee('Article 16')
            ->assertSee('src="https://example.com/article-16.jpg"', false)
            ->assertSeeInOrder(['Example News', 'Example Author'])
            ->assertSee('A normalized article summary.')
            ->assertSee('news-article-image', false)
            ->assertSee('Article 2')
            ->assertDontSee('href="https://example.com/articles/1"', false)
            ->assertSeeInOrder(['Article 16', 'Article 15', 'Article 14'])
            ->assertViewHas('newsArticles', fn ($articles) => $articles->count() === 15);
    }
}
