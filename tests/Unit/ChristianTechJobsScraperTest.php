<?php

namespace Tests\Unit;

use App\Services\JobScraping\ChristianTechJobsScraper;
use App\Services\JobScraping\HttpClient;
use PHPUnit\Framework\TestCase;

class ChristianTechJobsScraperTest extends TestCase
{
    public function test_it_keeps_recent_remote_jobs_matching_the_keywords(): void
    {
        $recentDate = date('c');
        $oldDate = date('c', strtotime('-10 days'));
        $http = new class($recentDate, $oldDate) extends HttpClient
        {
            /** @var string[] */
            public array $urls = [];

            public function __construct(
                private readonly string $recentDate,
                private readonly string $oldDate,
            ) {}

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->urls[] = $url;
                $jobs = [[
                    'id' => 1756,
                    'job_id' => 'remote platform engineer planning center',
                    'jobTitle' => 'Platform Engineer',
                    'company' => 'Planning Center',
                    'location' => 'Remote',
                    'dateAdded' => $this->recentDate,
                    'categories' => 'software development',
                    'skills' => 'Ruby on Rails,AWS,CI/CD',
                    'minSalary' => 200000,
                    'maxSalary' => 235000,
                    'salaryType' => 'annual',
                ], [
                    'id' => 1700,
                    'job_id' => 'local platform engineer example',
                    'jobTitle' => 'Platform Engineer',
                    'company' => 'Example',
                    'location' => 'Dallas, TX',
                    'dateAdded' => $this->recentDate,
                    'skills' => 'AWS',
                ], [
                    'id' => 1600,
                    'job_id' => 'remote old platform engineer example',
                    'jobTitle' => 'Platform Engineer',
                    'company' => 'Old Example',
                    'location' => 'Remote',
                    'dateAdded' => $this->oldDate,
                    'skills' => 'AWS',
                ]];
                $data = json_encode(['props' => ['pageProps' => ['jobs' => $jobs, 'pageCount' => 1]]]);

                return '<script id="__NEXT_DATA__" type="application/json">'.$data.'</script>';
            }
        };

        $result = (new ChristianTechJobsScraper($http, 5))->scrape('platform aws remote');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('Christian Tech Jobs', $result['jobs'][0]->source);
        $this->assertSame('Platform Engineer', $result['jobs'][0]->title);
        $this->assertSame('Planning Center', $result['jobs'][0]->company);
        $this->assertSame('Remote', $result['jobs'][0]->location);
        $this->assertSame('https://www.christiantechjobs.io/christian-jobs/remote-platform-engineer-planning-center-1756', $result['jobs'][0]->url);
        $this->assertSame('$200,000 - $235,000 per year', $result['jobs'][0]->salary);
        $this->assertSame('https://www.christiantechjobs.io/?remote=remote&page=1', $http->urls[0]);
    }
}
