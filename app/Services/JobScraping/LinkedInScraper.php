<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class LinkedInScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
        private readonly int $maxPages = 3,
    ) {}

    public function scrape(string $keywords): array
    {
        $encodedKeywords = rawurlencode($keywords);
        $seconds = $this->maxDays * 24 * 60 * 60;
        $baseUrl = "https://www.linkedin.com/jobs/search/?keywords={$encodedKeywords}&f_TPR=r{$seconds}&sortBy=DD";
        $jobs = [];
        $seenUrls = [];

        for ($page = 0; $page < $this->maxPages; $page++) {
            $url = $page === 0
                ? $baseUrl
                : "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search?keywords={$encodedKeywords}&f_TPR=r{$seconds}&sortBy=DD&start=".($page * 25);

            $html = $this->http->get($url);
            if ($html === null) {
                return [
                    'jobs' => $jobs,
                    'error' => $page === 0 ? 'LinkedIn request failed or was blocked.' : null,
                ];
            }

            $pageJobs = $this->parseJobCards($html);
            if ($pageJobs === []) {
                break;
            }

            foreach ($pageJobs as $job) {
                if (isset($seenUrls[$job->url])) {
                    continue;
                }

                $seenUrls[$job->url] = true;
                $jobs[] = $job;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    /**
     * @return JobListingData[]
     */
    private function parseJobCards(string $html): array
    {
        $jobs = [];
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//div[contains(@class, 'job-search-card')]");

        if ($cards === false) {
            return [];
        }

        foreach ($cards as $card) {
            if (! $card instanceof DOMElement) {
                continue;
            }

            $linkNode = $this->queryOne($xpath, ".//a[contains(@class, 'base-card__full-link')]", $card);
            $titleNode = $this->queryOne($xpath, ".//h3[contains(@class, 'base-search-card__title')]", $card);
            $companyNode = $this->queryOne($xpath, ".//h4[contains(@class, 'base-search-card__subtitle')]//a", $card)
                ?? $this->queryOne($xpath, ".//h4[contains(@class, 'base-search-card__subtitle')]", $card);
            $locationNode = $this->queryOne($xpath, ".//span[contains(@class, 'job-search-card__location')]", $card);
            $dateNode = $this->queryOne($xpath, ".//time[contains(@class, 'job-search-card__listdate')]", $card);

            $url = $linkNode instanceof DOMElement ? html_entity_decode($linkNode->getAttribute('href'), ENT_QUOTES | ENT_HTML5) : '';
            $title = $this->nodeText($titleNode);
            $company = $this->nodeText($companyNode);
            $location = $this->nodeText($locationNode);
            $postedDate = $dateNode instanceof DOMElement ? $dateNode->getAttribute('datetime') : null;
            $postedRelative = $this->nodeText($dateNode);

            if ($title === '' || $url === '' || ! DateFilter::isWithinDays($postedDate, $this->maxDays)) {
                continue;
            }

            $jobs[] = new JobListingData(
                source: 'LinkedIn',
                title: $title,
                company: $company,
                location: $location,
                url: $this->cleanUrl($url),
                postedDate: $postedDate ?: null,
                postedRelative: $postedRelative ?: null,
            );
        }

        return $jobs;
    }

    private function nodeText(?DOMNode $node): string
    {
        if ($node === null) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
    }

    private function queryOne(DOMXPath $xpath, string $expression, DOMNode $context): ?DOMNode
    {
        $nodes = $xpath->query($expression, $context);
        $node = $nodes === false ? null : $nodes->item(0);

        return $node instanceof DOMNode ? $node : null;
    }

    private function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $clean = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return $clean.($parts['path'] ?? '');
    }
}
