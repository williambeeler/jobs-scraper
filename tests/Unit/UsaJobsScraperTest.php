<?php

namespace Tests\Unit;

use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\UsaJobsScraper;
use PHPUnit\Framework\TestCase;

class UsaJobsScraperTest extends TestCase
{
    public function test_it_uses_the_remote_json_search_and_parses_jobs(): void
    {
        $recentDate = date('Y-m-d');
        $http = new class($recentDate) extends HttpClient
        {
            /** @var string[] */
            public array $urls = [];

            /** @var array<string, mixed> */
            public array $payload = [];

            /** @var string[] */
            public array $headers = [];

            public function __construct(private readonly string $recentDate) {}

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->urls[] = $url;

                return '<html></html>';
            }

            public function postJsonIncludingErrors(string $url, array $payload): ?string
            {
                return '<meta name="request-verification-token" content="test-token">';
            }

            public function postJson(string $url, array $payload, array $extraHeaders = []): ?string
            {
                $this->urls[] = $url;
                $this->payload = $payload;
                $this->headers = $extraHeaders;

                return json_encode([
                    'IsValid' => true,
                    'Pager' => ['HasNextPage' => false],
                    'Jobs' => [[
                        'Title' => 'Aerospace Engineer',
                        'Agency' => 'National Aeronautics and Space Administration',
                        'LocationDisplay' => 'Anywhere in the U.S. (remote job)',
                        'SalaryDisplay' => '$120,000 - $150,000 per year',
                        'DocumentID' => '877642500',
                        'PositionStartDate' => $this->recentDate,
                    ]],
                ]);
            }
        };

        $result = (new UsaJobsScraper($http, 5))->scrape('nasa');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('USAJOBS', $result['jobs'][0]->source);
        $this->assertSame('Aerospace Engineer', $result['jobs'][0]->title);
        $this->assertSame('National Aeronautics and Space Administration', $result['jobs'][0]->company);
        $this->assertSame('Anywhere in the U.S. (remote job)', $result['jobs'][0]->location);
        $this->assertSame('https://www.usajobs.gov/job/877642500', $result['jobs'][0]->url);
        $this->assertSame('$120,000 - $150,000 per year', $result['jobs'][0]->salary);
        $this->assertSame('https://www.usajobs.gov/search/results/?k=nasa&rmi=true&p=1', $http->urls[0]);
        $this->assertSame('https://www.usajobs.gov/Search/ExecuteSearch', $http->urls[1]);
        $this->assertSame('nasa', $http->payload['Keyword']);
        $this->assertTrue($http->payload['RemoteIndicator']);
        $this->assertContains('RequestVerificationToken: test-token', $http->headers);
    }

    public function test_no_matches_is_a_successful_empty_result(): void
    {
        $http = new class extends HttpClient
        {
            public function get(string $url, array $extraHeaders = []): ?string
            {
                return '<html></html>';
            }

            public function postJsonIncludingErrors(string $url, array $payload): ?string
            {
                return '<meta name="request-verification-token" content="test-token">';
            }

            public function postJson(string $url, array $payload, array $extraHeaders = []): ?string
            {
                return '{"IsValid":true,"Pager":{"HasNextPage":false},"Jobs":[]}';
            }
        };

        $result = (new UsaJobsScraper($http, 5))->scrape('no matches');

        $this->assertSame([], $result['jobs']);
        $this->assertNull($result['error']);
    }
}
