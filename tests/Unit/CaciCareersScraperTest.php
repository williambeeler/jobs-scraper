<?php

namespace Tests\Unit;

use App\Services\JobScraping\CaciCareersScraper;
use App\Services\JobScraping\HttpClient;
use PHPUnit\Framework\TestCase;

class CaciCareersScraperTest extends TestCase
{
    public function test_it_keeps_only_recent_oklahoma_city_area_or_remote_jobs(): void
    {
        $recentTimestamp = strtotime('today');
        $oldTimestamp = strtotime('-10 days');
        $http = new class($recentTimestamp, $oldTimestamp) extends HttpClient
        {
            /** @var string[] */
            public array $urls = [];

            /** @var array<int, string[]> */
            public array $headers = [];

            public function __construct(
                private readonly int $recentTimestamp,
                private readonly int $oldTimestamp,
            ) {}

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->urls[] = $url;
                $this->headers[] = $extraHeaders;

                return json_encode([
                    'status' => 200,
                    'data' => [
                        'count' => 4,
                        'positions' => [[
                            'id' => 1443151208346,
                            'name' => 'Enterprise Service Desk Technician',
                            'locations' => ['Oklahoma City, OK, US'],
                            'postedTs' => $this->recentTimestamp,
                        ], [
                            'id' => 1443152548173,
                            'name' => 'Cloud Support Engineer',
                            'locations' => ['Hanover, MD, US', 'Remote (Any State)'],
                            'postedTs' => $this->recentTimestamp,
                        ], [
                            'id' => 1443151415861,
                            'name' => 'Unrelated Onsite Role',
                            'locations' => ['Norfolk, VA, US'],
                            'postedTs' => $this->recentTimestamp,
                        ], [
                            'id' => 1443150000000,
                            'name' => 'Old Remote Role',
                            'locations' => ['Remote (Any State)'],
                            'postedTs' => $this->oldTimestamp,
                        ]],
                    ],
                ]);
            }
        };

        $result = (new CaciCareersScraper($http, 5))->scrape('support engineer');

        $this->assertNull($result['error']);
        $this->assertCount(2, $result['jobs']);
        $this->assertSame(['Enterprise Service Desk Technician', 'Cloud Support Engineer'], array_column($result['jobs'], 'title'));
        $this->assertSame('CACI Careers', $result['jobs'][0]->source);
        $this->assertSame('CACI', $result['jobs'][0]->company);
        $this->assertSame('Oklahoma City, OK, US', $result['jobs'][0]->location);
        $this->assertSame('https://searchcareers.caci.com/careers/job/1443151208346', $result['jobs'][0]->url);
        $this->assertStringContainsString('query=support%20engineer', $http->urls[0]);
        $this->assertStringContainsString('location=Oklahoma%20City%2C%20Oklahoma%2C%20United%20States', $http->urls[0]);
        $this->assertStringContainsString('filter_include_remote=1', $http->urls[0]);
        $this->assertSame(['Accept: application/json'], $http->headers[0]);
    }

    public function test_an_empty_caci_response_is_a_successful_empty_result(): void
    {
        $http = new class extends HttpClient
        {
            public function get(string $url, array $extraHeaders = []): ?string
            {
                return '{"status":200,"data":{"count":0,"positions":[]}}';
            }
        };

        $result = (new CaciCareersScraper($http, 5))->scrape('no matches');

        $this->assertSame([], $result['jobs']);
        $this->assertNull($result['error']);
    }
}
