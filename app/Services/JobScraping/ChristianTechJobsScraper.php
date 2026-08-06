<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use Illuminate\Support\Str;
use JsonException;

final class ChristianTechJobsScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
        private readonly int $maxPages = 3,
    ) {}

    public function scrape(string $keywords): array
    {
        $terms = $this->searchTerms($keywords);
        $jobs = [];
        $seenUrls = [];

        for ($page = 1; $page <= $this->maxPages; $page++) {
            $url = 'https://www.christiantechjobs.io/?'.http_build_query([
                'remote' => 'remote',
                'page' => $page,
            ], '', '&', PHP_QUERY_RFC3986);
            $html = $this->http->get($url);

            if ($html === null) {
                return [
                    'jobs' => $jobs,
                    'error' => $page === 1 ? 'Christian Tech Jobs request failed or was blocked.' : null,
                ];
            }

            $pageData = $this->pageData($html);
            if ($pageData === null) {
                return [
                    'jobs' => $jobs,
                    'error' => $page === 1 ? 'Christian Tech Jobs returned an invalid page response.' : null,
                ];
            }

            foreach ($pageData['items'] as $item) {
                $job = $this->jobFromItem($item, $terms);
                if ($job === null || isset($seenUrls[$job->url])) {
                    continue;
                }

                $seenUrls[$job->url] = true;
                $jobs[] = $job;
            }

            if ($page >= $pageData['pageCount']) {
                break;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pageCount: int}|null
     */
    private function pageData(string $html): ?array
    {
        if (preg_match('#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#s', $html, $matches) !== 1) {
            return null;
        }

        try {
            $data = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $page = is_array($data) ? ($data['props']['pageProps'] ?? null) : null;
        $items = is_array($page) ? ($page['jobs'] ?? null) : null;

        if (! is_array($items)) {
            return null;
        }

        return [
            'items' => array_values(array_filter($items, 'is_array')),
            'pageCount' => max(1, (int) ($page['pageCount'] ?? 1)),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  string[]  $terms
     */
    private function jobFromItem(array $item, array $terms): ?JobListingData
    {
        $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $title = trim((string) ($item['jobTitle'] ?? ''));
        $company = trim((string) ($item['company'] ?? ''));
        $location = trim((string) ($item['location'] ?? ''));
        $postedDate = trim((string) ($item['dateAdded'] ?? ''));
        $jobId = trim((string) ($item['job_id'] ?? ''));

        if (
            $id === false
            || $title === ''
            || $jobId === ''
            || mb_strtolower($location) !== 'remote'
            || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
            || ! $this->matchesTerms($item, $terms)
        ) {
            return null;
        }

        return new JobListingData(
            source: 'Christian Tech Jobs',
            title: $title,
            company: $company,
            location: 'Remote',
            url: 'https://www.christiantechjobs.io/christian-jobs/'.Str::slug($jobId).'-'.$id,
            postedDate: $postedDate,
            salary: $this->salary($item),
        );
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
            (string) ($item['jobTitle'] ?? ''),
            (string) ($item['company'] ?? ''),
            (string) ($item['categories'] ?? ''),
            (string) ($item['skills'] ?? ''),
        ]));

        foreach ($terms as $term) {
            if (! str_contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $item */
    private function salary(array $item): ?string
    {
        $minimum = is_numeric($item['minSalary'] ?? null) ? (float) $item['minSalary'] : null;
        $maximum = is_numeric($item['maxSalary'] ?? null) ? (float) $item['maxSalary'] : null;

        if ($minimum === null && $maximum === null) {
            return null;
        }

        $range = match (true) {
            $minimum !== null && $maximum !== null => $this->money($minimum).' - '.$this->money($maximum),
            $minimum !== null => 'From '.$this->money($minimum),
            default => 'Up to '.$this->money((float) $maximum),
        };
        $interval = match (mb_strtolower((string) ($item['salaryType'] ?? ''))) {
            'hourly' => ' per hour',
            'annual' => ' per year',
            default => '',
        };

        return $range.$interval;
    }

    private function money(float $amount): string
    {
        $decimals = floor($amount) === $amount ? 0 : 2;

        return '$'.number_format($amount, $decimals);
    }
}
