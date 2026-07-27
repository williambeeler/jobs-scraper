<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class LaraJobsScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    public function scrape(string $keywords): array
    {
        $xml = $this->http->get('https://larajobs.com/feed');
        if ($xml === null) {
            return ['jobs' => [], 'error' => 'LaraJobs feed request failed or was blocked.'];
        }

        $result = $this->parseFeed($xml);

        return [
            'jobs' => $result['jobs'],
            'error' => $result['itemsFound']
                ? null
                : 'LaraJobs feed loaded but no job entries were found.',
        ];
    }

    /**
     * @return array{jobs: JobListingData[], itemsFound: bool}
     */
    private function parseFeed(string $xml): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();

        if (! $loaded) {
            return ['jobs' => [], 'itemsFound' => false];
        }

        $xpath = new DOMXPath($dom);
        $items = $xpath->query('/rss/channel/item');
        if ($items === false) {
            return ['jobs' => [], 'itemsFound' => false];
        }

        $jobs = [];

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $title = $this->nodeText($this->queryOne($xpath, './title', $item));
            $company = $this->nodeText($this->queryOne($xpath, "./*[local-name()='company']", $item));
            if ($company === '') {
                $company = $this->nodeText($this->queryOne($xpath, "./*[local-name()='creator']", $item));
            }

            $location = $this->nodeText($this->queryOne($xpath, "./*[local-name()='location']", $item));
            $salary = $this->nodeText($this->queryOne($xpath, "./*[local-name()='salary']", $item));
            $postedDate = $this->nodeText($this->queryOne($xpath, './pubDate', $item));
            $url = $this->nodeText($this->queryOne($xpath, './link', $item));

            if (
                $title === ''
                || ! $this->isLaraJobsUrl($url)
                || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
            ) {
                continue;
            }

            $jobs[] = new JobListingData(
                source: 'LaraJobs',
                title: $title,
                company: $company,
                location: $location,
                url: $url,
                postedDate: $postedDate,
                salary: $salary !== '' ? $salary : null,
            );
        }

        return ['jobs' => $jobs, 'itemsFound' => $items->length > 0];
    }

    private function isLaraJobsUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $host === 'larajobs.com' && preg_match('#^/job/\d+/?$#', $path) === 1;
    }

    private function nodeText(?DOMNode $node): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $node?->textContent ?? ''));
    }

    private function queryOne(DOMXPath $xpath, string $expression, DOMNode $context): ?DOMNode
    {
        $nodes = $xpath->query($expression, $context);
        $node = $nodes === false ? null : $nodes->item(0);

        return $node instanceof DOMNode ? $node : null;
    }
}
