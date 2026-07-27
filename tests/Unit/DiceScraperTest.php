<?php

namespace Tests\Unit;

use App\Services\JobScraping\DiceScraper;
use App\Services\JobScraping\HttpClient;
use PHPUnit\Framework\TestCase;

class DiceScraperTest extends TestCase
{
    public function test_it_keeps_only_recent_remote_dice_jobs(): void
    {
        $http = new class extends HttpClient
        {
            public string $url = '';

            public function get(string $url, array $extraHeaders = []): ?string
            {
                $this->url = $url;

                return <<<'HTML'
                    <div class="card">
                        <a data-testid="job-search-job-card-link" href="/job-detail/abc-123"></a>
                        <a href="/company-profile/company-1">Acme Corp</a>
                        <div class="content">
                            <a data-testid="job-search-job-detail-link" href="/job-detail/abc-123">WordPress Developer</a>
                            <p>Remote</p><p>3d ago</p>
                        </div>
                    </div>
                    <div class="card">
                        <a data-testid="job-search-job-card-link" href="/job-detail/old-456"></a>
                        <a href="/company-profile/company-2">Old Corp</a>
                        <div class="content">
                            <a data-testid="job-search-job-detail-link" href="/job-detail/old-456">Old Developer</a>
                            <p>Remote</p><p>2w ago</p>
                        </div>
                    </div>
                    <div class="card">
                        <a data-testid="job-search-job-card-link" href="/job-detail/local-789"></a>
                        <a href="/company-profile/company-3">Local Corp</a>
                        <div class="content">
                            <a data-testid="job-search-job-detail-link" href="/job-detail/local-789">Local Developer</a>
                            <p>Austin, Texas</p><p>1d ago</p>
                        </div>
                    </div>
                    HTML;
            }
        };

        $result = (new DiceScraper($http, 5))->scrape('wordpress developer');

        $this->assertNull($result['error']);
        $this->assertCount(1, $result['jobs']);
        $this->assertSame('Dice', $result['jobs'][0]->source);
        $this->assertSame('WordPress Developer', $result['jobs'][0]->title);
        $this->assertSame('Acme Corp', $result['jobs'][0]->company);
        $this->assertSame('Remote', $result['jobs'][0]->location);
        $this->assertSame('https://www.dice.com/job-detail/abc-123', $result['jobs'][0]->url);
        $this->assertSame('https://www.dice.com/jobs/q-wordpress+developer-jobs', $http->url);
    }
}
