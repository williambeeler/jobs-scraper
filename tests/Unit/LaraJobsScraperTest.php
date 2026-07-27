<?php

namespace Tests\Unit;

use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\LaraJobsScraper;
use PHPUnit\Framework\TestCase;

class LaraJobsScraperTest extends TestCase
{
    public function test_it_sweeps_recent_jobs_from_the_rss_feed_without_using_keywords(): void
    {
        $recentDate = date(DATE_RSS);
        $oldDate = date(DATE_RSS, strtotime('-10 days'));
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

                return <<<XML
                    <?xml version="1.0" encoding="UTF-8"?>
                    <rss version="2.0"
                        xmlns:dc="http://purl.org/dc/elements/1.1/"
                        xmlns:job="https://larajobs.com">
                        <channel>
                            <item>
                                <title>Laravel Engineer</title>
                                <link>https://larajobs.com/job/1234</link>
                                <pubDate>{$this->recentDate}</pubDate>
                                <dc:creator>Acme</dc:creator>
                                <job:location>Remote / USA</job:location>
                                <job:salary>$120,000</job:salary>
                                <job:company>Acme</job:company>
                            </item>
                            <item>
                                <title>Old Laravel Engineer</title>
                                <link>https://larajobs.com/job/1200</link>
                                <pubDate>{$this->oldDate}</pubDate>
                                <job:company>Old Co</job:company>
                            </item>
                        </channel>
                    </rss>
                    XML;
            }
        };

        $result = (new LaraJobsScraper($http, 5))->scrape('ignored keywords');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('LaraJobs', $result['jobs'][0]->source);
        $this->assertSame('Laravel Engineer', $result['jobs'][0]->title);
        $this->assertSame('Acme', $result['jobs'][0]->company);
        $this->assertSame('Remote / USA', $result['jobs'][0]->location);
        $this->assertSame('$120,000', $result['jobs'][0]->salary);
        $this->assertSame('https://larajobs.com/job/1234', $result['jobs'][0]->url);
        $this->assertSame('https://larajobs.com/feed', $http->url);
    }
}
