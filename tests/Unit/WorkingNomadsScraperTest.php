<?php

namespace Tests\Unit;

use App\Services\JobScraping\HttpClient;
use App\Services\JobScraping\WorkingNomadsScraper;
use PHPUnit\Framework\TestCase;

class WorkingNomadsScraperTest extends TestCase
{
    public function test_it_filters_recent_api_jobs_by_keywords(): void
    {
        $recentDate = date(DATE_ATOM);
        $oldDate = date(DATE_ATOM, strtotime('-10 days'));
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

                return json_encode([
                    [
                        'url' => 'https://www.workingnomads.com/job/go/1234/',
                        'title' => 'Senior WordPress Developer',
                        'company_name' => 'Acme',
                        'category_name' => 'Development',
                        'tags' => 'wordpress,php',
                        'location' => 'North America',
                        'pub_date' => $this->recentDate,
                    ],
                    [
                        'url' => 'https://www.workingnomads.com/job/go/1235/',
                        'title' => 'Product Designer',
                        'company_name' => 'Design Co',
                        'category_name' => 'Design',
                        'tags' => 'figma,ux',
                        'location' => 'Anywhere',
                        'description' => 'A generic footer happens to mention WordPress.',
                        'pub_date' => $this->recentDate,
                    ],
                    [
                        'url' => 'https://www.workingnomads.com/job/go/1200/',
                        'title' => 'Old WordPress Developer',
                        'tags' => 'wordpress',
                        'pub_date' => $this->oldDate,
                    ],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $result = (new WorkingNomadsScraper($http, 5))->scrape('wordpress remote');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('Working Nomads', $result['jobs'][0]->source);
        $this->assertSame('Senior WordPress Developer', $result['jobs'][0]->title);
        $this->assertSame('Acme', $result['jobs'][0]->company);
        $this->assertSame('North America', $result['jobs'][0]->location);
        $this->assertSame('https://www.workingnomads.com/job/go/1234/', $result['jobs'][0]->url);
        $this->assertSame('https://www.workingnomads.com/api/exposed_jobs/', $http->url);
    }
}
