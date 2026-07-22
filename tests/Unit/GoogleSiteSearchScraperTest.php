<?php

namespace Tests\Unit;

use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\IndeedScraper;
use App\Services\JobScraping\ZipRecruiterScraper;
use PHPUnit\Framework\TestCase;

class GoogleSiteSearchScraperTest extends TestCase
{
    public function test_indeed_uses_google_site_search_when_the_native_request_is_blocked(): void
    {
        $http = $this->fakeHttp([
            null,
            '<a href="/url?q=https%3A%2F%2Fwww.indeed.com%2Fviewjob%3Fjk%3Dabc123%26from%3Dgoogle"><h3>WordPress Developer - Indeed</h3></a>',
            '',
        ]);

        $result = (new IndeedScraper($http, 5))->scrape('wordpress developer');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('Indeed', $result['jobs'][0]->source);
        $this->assertSame('WordPress Developer', $result['jobs'][0]->title);
        $this->assertSame('https://www.indeed.com/viewjob?jk=abc123', $result['jobs'][0]->url);
        $this->assertStringContainsString('site%3Aindeed.com%2Fviewjob', $http->urls[1]);
    }

    public function test_ziprecruiter_google_fallback_rejects_results_from_other_sites(): void
    {
        $http = $this->fakeHttp([
            null,
            <<<'HTML'
                <a href="https://www.indeed.com/viewjob?jk=wrong"><h3>Wrong source</h3></a>
                <a href="https://www.ziprecruiter.com/jobs/example/job/abc?lvk=tracking"><h3>Laravel Developer | ZipRecruiter</h3></a>
                HTML,
            '',
        ]);

        $result = (new ZipRecruiterScraper($http, 5))->scrape('laravel developer');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('ZipRecruiter', $result['jobs'][0]->source);
        $this->assertSame('Laravel Developer', $result['jobs'][0]->title);
        $this->assertSame('https://www.ziprecruiter.com/jobs/example/job/abc', $result['jobs'][0]->url);
    }

    /**
     * @param  array<int, string|null>  $responses
     */
    private function fakeHttp(array $responses): HttpClient
    {
        return new class($responses) extends HttpClient
        {
            /** @var string[] */
            public array $urls = [];

            /**
             * @param  array<int, string|null>  $responses
             */
            public function __construct(private array $responses) {}

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->urls[] = $url;

                return array_shift($this->responses);
            }
        };
    }
}
