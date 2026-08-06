<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use JsonException;

final class UsaJobsScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
        private readonly int $maxPages = 3,
    ) {}

    public function scrape(string $keywords): array
    {
        $keywords = trim($keywords);
        $jobs = [];
        $seenUrls = [];

        for ($page = 1; $page <= $this->maxPages; $page++) {
            $searchUrl = $this->searchUrl($keywords, $page);
            $html = $this->http->get($searchUrl);

            if ($html === null) {
                return $this->failure($jobs, $page, 'USAJOBS request failed or was blocked.');
            }

            $payload = [
                'Keyword' => $keywords,
                'RemoteIndicator' => true,
                'Page' => $page,
                'IsAuthenticated' => false,
            ];
            $bootstrap = $this->http->postJsonIncludingErrors(
                'https://www.usajobs.gov/Search/ExecuteSearch',
                $payload,
            );
            $json = $this->isValidSearchJson($bootstrap) ? $bootstrap : null;

            if ($json === null) {
                $token = $this->verificationToken((string) $bootstrap);
                if ($token === null) {
                    return $this->failure($jobs, $page, 'USAJOBS did not provide a valid search response.');
                }

                $json = $this->http->postJson('https://www.usajobs.gov/Search/ExecuteSearch', $payload, [
                    'RequestVerificationToken: '.$token,
                    'Referer: '.$searchUrl,
                ]);
            }

            if ($json === null) {
                return $this->failure($jobs, $page, 'USAJOBS search request failed or was blocked.');
            }

            try {
                $response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->failure($jobs, $page, 'USAJOBS returned an invalid search response.');
            }

            if (! is_array($response) || ($response['IsValid'] ?? false) !== true) {
                return $this->failure($jobs, $page, 'USAJOBS rejected the search request.');
            }

            $items = $response['Jobs'] ?? [];
            if (! is_array($items) || $items === []) {
                break;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $job = $this->jobFromItem($item);
                if ($job === null || isset($seenUrls[$job->url])) {
                    continue;
                }

                $seenUrls[$job->url] = true;
                $jobs[] = $job;
            }

            if (($response['Pager']['HasNextPage'] ?? false) !== true) {
                break;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    private function searchUrl(string $keywords, int $page): string
    {
        $query = http_build_query([
            'k' => $keywords,
            'rmi' => 'true',
            'p' => $page,
        ], '', '&', PHP_QUERY_RFC3986);

        return "https://www.usajobs.gov/search/results/?{$query}";
    }

    private function verificationToken(string $html): ?string
    {
        if (preg_match('/<meta\s+name=["\']request-verification-token["\']\s+content=["\']([^"\']+)["\']/i', $html, $matches) !== 1) {
            return null;
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
    }

    private function isValidSearchJson(?string $json): bool
    {
        if ($json === null) {
            return false;
        }

        try {
            $response = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return is_array($response) && ($response['IsValid'] ?? false) === true;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function jobFromItem(array $item): ?JobListingData
    {
        $title = trim((string) ($item['Title'] ?? ''));
        $documentId = trim((string) ($item['DocumentID'] ?? ''));
        $postedDate = trim((string) ($item['PositionStartDate'] ?? ''));

        if (
            $title === ''
            || preg_match('/^\d+$/', $documentId) !== 1
            || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
        ) {
            return null;
        }

        return new JobListingData(
            source: 'USAJOBS',
            title: $title,
            company: trim((string) ($item['Agency'] ?? $item['Department'] ?? '')),
            location: trim((string) ($item['LocationDisplay'] ?? $item['Location'] ?? 'Remote')) ?: 'Remote',
            url: "https://www.usajobs.gov/job/{$documentId}",
            postedDate: $postedDate,
            salary: ($salary = trim((string) ($item['SalaryDisplay'] ?? ''))) !== '' ? $salary : null,
        );
    }

    /**
     * @param  JobListingData[]  $jobs
     * @return array{jobs: JobListingData[], error: ?string}
     */
    private function failure(array $jobs, int $page, string $message): array
    {
        return [
            'jobs' => $jobs,
            'error' => $page === 1 ? $message : null,
        ];
    }
}
