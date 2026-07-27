<?php

namespace Tests\Unit;

use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\WordPressJobsScraper;
use PHPUnit\Framework\TestCase;

class WordPressJobsScraperTest extends TestCase
{
    public function test_it_sweeps_recent_jobs_without_using_keywords(): void
    {
        $recentDate = date('F j, Y');
        $oldDate = date('F j, Y', strtotime('-10 days'));
        $http = new class($recentDate, $oldDate) extends HttpClient
        {
            public string $url = '';

            public function __construct(
                private readonly string $recentDate,
                private readonly string $oldDate,
            ) {}

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->url = $url;

                return <<<HTML
                    <a href="https://jobs.wordpress.net/job/recent-role/" class="job-card">
                        <h2 class="job-card__title">WordPress Engineer</h2>
                        <p class="job-card__company">Acme</p>
                        <div class="job-card__meta"><span>&#127758; Remote</span><span>Full Time</span></div>
                        <p class="job-card__date">Posted {$this->recentDate}</p>
                    </a>
                    <a href="https://jobs.wordpress.net/job/old-role/" class="job-card">
                        <h2 class="job-card__title">Old Engineer</h2>
                        <p class="job-card__company">Old Co</p>
                        <div class="job-card__meta"><span>Europe</span></div>
                        <p class="job-card__date">Posted {$this->oldDate}</p>
                    </a>
                    HTML;
            }
        };

        $result = (new WordPressJobsScraper($http, 5))->scrape('ignored keywords');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('WordPress Jobs', $result['jobs'][0]->source);
        $this->assertSame('WordPress Engineer', $result['jobs'][0]->title);
        $this->assertSame('Acme', $result['jobs'][0]->company);
        $this->assertSame('Remote', $result['jobs'][0]->location);
        $this->assertSame('https://jobs.wordpress.net/job/recent-role/', $result['jobs'][0]->url);
        $this->assertSame('https://jobs.wordpress.net/', $http->url);
    }
}
