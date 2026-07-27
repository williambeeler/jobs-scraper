<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use JsonException;

final class WorkingNomadsScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    public function scrape(string $keywords): array
    {
        $json = $this->http->get('https://www.workingnomads.com/api/exposed_jobs/');
        if ($json === null) {
            return ['jobs' => [], 'error' => 'Working Nomads API request failed or was blocked.'];
        }

        try {
            $items = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['jobs' => [], 'error' => 'Working Nomads API returned invalid JSON.'];
        }

        if (! is_array($items)) {
            return ['jobs' => [], 'error' => 'Working Nomads API returned an unexpected response.'];
        }

        $terms = $this->searchTerms($keywords);
        $jobs = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            $company = trim((string) ($item['company_name'] ?? ''));
            $location = trim((string) ($item['location'] ?? ''));
            $postedDate = trim((string) ($item['pub_date'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if (
                $title === ''
                || ! $this->isWorkingNomadsUrl($url)
                || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
                || ! $this->matchesTerms($item, $terms)
            ) {
                continue;
            }

            $jobs[] = new JobListingData(
                source: 'Working Nomads',
                title: $title,
                company: $company,
                location: $location !== '' ? $location : 'Remote',
                url: $url,
                postedDate: $postedDate !== '' ? $postedDate : null,
            );
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    /**
     * @return string[]
     */
    private function searchTerms(string $keywords): array
    {
        $terms = preg_split('/\s+/u', mb_strtolower(trim($keywords))) ?: [];

        return array_values(array_unique(array_filter(
            $terms,
            fn (string $term): bool => $term !== '' && $term !== 'remote',
        )));
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  string[]  $terms
     */
    private function matchesTerms(array $item, array $terms): bool
    {
        $haystack = mb_strtolower(implode(' ', [
            (string) ($item['title'] ?? ''),
            (string) ($item['company_name'] ?? ''),
            (string) ($item['category_name'] ?? ''),
            (string) ($item['tags'] ?? ''),
            (string) ($item['location'] ?? ''),
        ]));

        foreach ($terms as $term) {
            if (! str_contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    }

    private function isWorkingNomadsUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        return ($host === 'workingnomads.com' || $host === 'www.workingnomads.com')
            && preg_match('#^/job/go/\d+/?$#', $path) === 1;
    }
}
