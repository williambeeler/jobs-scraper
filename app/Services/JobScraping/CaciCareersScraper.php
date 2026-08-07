<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use JsonException;

final class CaciCareersScraper implements JobSourceScraper
{
    private const PAGE_SIZE = 10;

    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
        private readonly int $maxPages = 5,
    ) {}

    public function scrape(string $keywords): array
    {
        $keywords = trim($keywords);
        $jobs = [];
        $seenUrls = [];

        for ($page = 1; $page <= $this->maxPages; $page++) {
            $offset = ($page - 1) * self::PAGE_SIZE;
            $json = $this->http->get($this->searchUrl($keywords, $offset), ['Accept: application/json']);
            if ($json === null) {
                return $this->failure($jobs, $page, 'CACI Careers request failed or was blocked.');
            }

            try {
                $response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->failure($jobs, $page, 'CACI Careers returned an invalid search response.');
            }

            $data = is_array($response) ? ($response['data'] ?? null) : null;
            $positions = is_array($data) ? ($data['positions'] ?? null) : null;
            if (! is_array($positions)) {
                return $this->failure($jobs, $page, 'CACI Careers returned an invalid search response.');
            }

            foreach ($positions as $position) {
                if (! is_array($position)) {
                    continue;
                }

                $job = $this->jobFromPosition($position);
                if ($job === null || isset($seenUrls[$job->url])) {
                    continue;
                }

                $seenUrls[$job->url] = true;
                $jobs[] = $job;
            }

            $count = max(0, (int) ($data['count'] ?? 0));
            if ($positions === [] || $offset + self::PAGE_SIZE >= $count) {
                break;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    private function searchUrl(string $keywords, int $offset): string
    {
        $query = http_build_query([
            'domain' => 'caci.com',
            'query' => $keywords,
            'location' => 'Oklahoma City, Oklahoma, United States',
            'start' => $offset,
            'filter_include_remote' => 1,
            'filter_include_relocation' => 0,
            'sort_by' => 'match',
        ], '', '&', PHP_QUERY_RFC3986);

        return "https://searchcareers.caci.com/api/pcsx/search?{$query}";
    }

    /** @param array<string, mixed> $position */
    private function jobFromPosition(array $position): ?JobListingData
    {
        $id = filter_var($position['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $title = trim((string) ($position['name'] ?? ''));
        $locations = array_values(array_filter(
            is_array($position['locations'] ?? null) ? $position['locations'] : [],
            fn (mixed $location): bool => is_string($location) && trim($location) !== '',
        ));
        $postedTimestamp = filter_var($position['postedTs'] ?? $position['creationTs'] ?? null, FILTER_VALIDATE_INT);
        $postedDate = $postedTimestamp === false ? '' : gmdate('Y-m-d', $postedTimestamp);

        if (
            $id === false
            || $title === ''
            || $locations === []
            || ! $this->isAllowedLocation($locations)
            || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
        ) {
            return null;
        }

        return new JobListingData(
            source: 'CACI Careers',
            title: $title,
            company: 'CACI',
            location: implode(' · ', $locations),
            url: "https://searchcareers.caci.com/careers/job/{$id}",
            postedDate: $postedDate,
            salary: null,
        );
    }

    /** @param string[] $locations */
    private function isAllowedLocation(array $locations): bool
    {
        foreach ($locations as $location) {
            if (preg_match('/\bremote\b/i', $location) === 1) {
                return true;
            }

            if (preg_match('/\b(?:Oklahoma City|Tinker AFB|Midwest City|Edmond|Moore|Norman)\b/i', $location) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @param JobListingData[] $jobs */
    private function failure(array $jobs, int $page, string $message): array
    {
        return ['jobs' => $jobs, 'error' => $page === 1 ? $message : null];
    }
}
