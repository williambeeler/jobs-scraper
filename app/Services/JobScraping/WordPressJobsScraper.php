<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class WordPressJobsScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    public function scrape(string $keywords): array
    {
        $html = $this->http->get('https://jobs.wordpress.net/');
        if ($html === null) {
            return ['jobs' => [], 'error' => 'WordPress Jobs request failed or was blocked.'];
        }

        $result = $this->parseJobCards($html);

        return [
            'jobs' => $result['jobs'],
            'error' => $result['cardsFound']
                ? null
                : 'WordPress Jobs page loaded but no job cards were found.',
        ];
    }

    /**
     * @return array{jobs: JobListingData[], cardsFound: bool}
     */
    private function parseJobCards(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//a[contains(concat(' ', normalize-space(@class), ' '), ' job-card ')]");
        if ($cards === false) {
            return ['jobs' => [], 'cardsFound' => false];
        }

        $jobs = [];

        foreach ($cards as $card) {
            if (! $card instanceof DOMElement) {
                continue;
            }

            $title = $this->nodeText($this->queryOne($xpath, ".//*[contains(@class, 'job-card__title')]", $card));
            $company = $this->nodeText($this->queryOne($xpath, ".//*[contains(@class, 'job-card__company')]", $card));
            $location = $this->nodeText($this->queryOne($xpath, ".//*[contains(@class, 'job-card__meta')]//span[1]", $card));
            $postedText = $this->nodeText($this->queryOne($xpath, ".//*[contains(@class, 'job-card__date')]", $card));
            $postedDate = trim((string) preg_replace('/^Posted\s+/i', '', $postedText));
            $url = html_entity_decode($card->getAttribute('href'), ENT_QUOTES | ENT_HTML5);

            if (
                $title === ''
                || ! $this->isWordPressJobsUrl($url)
                || ! DateFilter::isWithinDays($postedDate, $this->maxDays)
            ) {
                continue;
            }

            $jobs[] = new JobListingData(
                source: 'WordPress Jobs',
                title: $title,
                company: $company,
                location: $this->cleanLocation($location),
                url: $url,
                postedDate: $postedDate !== '' ? $postedDate : null,
            );
        }

        return ['jobs' => $jobs, 'cardsFound' => $cards->length > 0];
    }

    private function isWordPressJobsUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $host === 'jobs.wordpress.net' && str_starts_with($path, '/job/');
    }

    private function cleanLocation(string $location): string
    {
        return trim((string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $location));
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
