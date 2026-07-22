<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;

final class IndeedScraper extends GoogleSiteSearchScraper
{
    protected function scrapeNative(string $keywords): array
    {
        $query = rawurlencode($keywords);
        $url = "https://www.indeed.com/jobs?q={$query}&l=remote&fromage={$this->maxDays}&sort=date&radius=0";
        $html = $this->http->get($url, ['Referer: https://www.indeed.com/']);

        if ($html === null) {
            return ['jobs' => [], 'error' => 'Indeed blocked the request (Cloudflare protection). Try again later or from a residential network.'];
        }

        if ($this->isBlockedPage($html)) {
            return ['jobs' => [], 'error' => 'Indeed returned a security challenge page. Automated scraping is blocked without a real browser session.'];
        }

        $results = $this->extractMosaicResults($html);
        if ($results === []) {
            $results = $this->extractInitialDataResults($html);
        }

        if ($results === []) {
            return ['jobs' => [], 'error' => 'Indeed page loaded but no job listings were found in the response.'];
        }

        $jobs = [];
        foreach ($results as $result) {
            $job = $this->mapResult($result);
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    protected function sourceName(): string
    {
        return 'Indeed';
    }

    protected function googleSite(): string
    {
        return 'indeed.com/viewjob';
    }

    protected function acceptsGoogleUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return ($host === 'indeed.com' || str_ends_with($host, '.indeed.com'))
            && parse_url($url, PHP_URL_PATH) === '/viewjob';
    }

    protected function normalizeGoogleUrl(string $url): string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);
        $jobKey = $parameters['jk'] ?? null;

        return is_string($jobKey) && $jobKey !== ''
            ? 'https://www.indeed.com/viewjob?jk='.rawurlencode($jobKey)
            : parent::normalizeGoogleUrl($url);
    }

    private function isBlockedPage(string $html): bool
    {
        return str_contains($html, 'Security Check')
            || str_contains($html, 'Additional Verification Required')
            || str_contains($html, 'captcha')
            || str_contains($html, 'challenge-platform');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractMosaicResults(string $html): array
    {
        if (! preg_match('/window\.mosaic\.providerData\["mosaic-provider-jobcards"\]\s*=\s*(\{.+?\});/s', $html, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);

        return is_array($data) ? ($data['metaData']['mosaicProviderJobCardsModel']['results'] ?? []) : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractInitialDataResults(string $html): array
    {
        if (! preg_match('/window\._initialData\s*=\s*(\{.+?\});/s', $html, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);
        if (! is_array($data)) {
            return [];
        }

        foreach ([
            $data['mosaicProviderJobCardsModel']['results'] ?? null,
            $data['jobCards']['results'] ?? null,
            $data['searchTitleBarModel']['results'] ?? null,
        ] as $candidate) {
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function mapResult(array $result): ?JobListingData
    {
        $title = trim((string) ($result['title'] ?? $result['displayTitle'] ?? ''));
        $jobKey = trim((string) ($result['jobkey'] ?? ''));
        if ($title === '' || $jobKey === '') {
            return null;
        }

        $pubDate = isset($result['pubDate']) ? (int) $result['pubDate'] : null;
        if (! DateFilter::isTimestampWithinDays($pubDate, $this->maxDays)) {
            return null;
        }

        $url = 'https://www.indeed.com/viewjob?jk='.rawurlencode($jobKey);
        if (isset($result['viewJobLink']) && is_string($result['viewJobLink']) && str_starts_with($result['viewJobLink'], 'http')) {
            $url = $result['viewJobLink'];
        }

        return new JobListingData(
            source: 'Indeed',
            title: $title,
            company: trim((string) ($result['company'] ?? $result['truncatedCompany'] ?? '')),
            location: trim((string) ($result['formattedLocation'] ?? '')),
            url: $url,
            postedDate: $pubDate !== null ? date('Y-m-d', (int) floor($pubDate / 1000)) : null,
            postedRelative: trim((string) ($result['formattedRelativeTime'] ?? '')) ?: null,
            salary: trim((string) ($result['salarySnippet']['salaryText'] ?? '')) ?: null,
        );
    }
}
