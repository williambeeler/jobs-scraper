<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;

interface JobSourceScraper
{
    /**
     * @return array{jobs: JobListingData[], error: ?string}
     */
    public function scrape(string $keywords): array;
}
