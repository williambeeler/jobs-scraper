<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class ZipRecruiterScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    public function scrape(string $keywords): array
    {
        $query = rawurlencode($keywords);
        $url = "https://www.ziprecruiter.com/jobs-search?search={$query}&location=remote&days={$this->maxDays}";
        $html = $this->http->get($url);

        if ($html === null) {
            return ['jobs' => [], 'error' => 'ZipRecruiter blocked the request (HTTP error).'];
        }

        if ($this->isBlockedPage($html)) {
            return ['jobs' => [], 'error' => 'ZipRecruiter returned a Cloudflare challenge page. Automated scraping is blocked without a real browser session.'];
        }

        $jobs = $this->parseJsonLd($html);
        if ($jobs === []) {
            $jobs = $this->parseHtmlCards($html);
        }

        if ($jobs === []) {
            return ['jobs' => [], 'error' => 'ZipRecruiter page loaded but no job listings were found in the response.'];
        }

        return ['jobs' => $jobs, 'error' => null];
    }

    private function isBlockedPage(string $html): bool
    {
        return str_contains($html, 'Just a moment')
            || str_contains($html, 'challenge-platform')
            || str_contains($html, 'Enable JavaScript and cookies');
    }

    /**
     * @return JobListingData[]
     */
    private function parseJsonLd(string $html): array
    {
        $jobs = [];
        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true);
            if (! is_array($decoded)) {
                continue;
            }

            $items = isset($decoded['@type']) ? [$decoded] : $decoded;
            foreach ($items as $item) {
                if (! is_array($item) || ($item['@type'] ?? '') !== 'JobPosting') {
                    continue;
                }

                $job = $this->mapJsonLdJob($item);
                if ($job !== null) {
                    $jobs[] = $job;
                }
            }
        }

        return $jobs;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapJsonLdJob(array $item): ?JobListingData
    {
        $title = trim((string) ($item['title'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        $postedDate = trim((string) ($item['datePosted'] ?? ''));

        if ($title === '' || $url === '') {
            return null;
        }

        if ($postedDate !== '' && ! DateFilter::isWithinDays(substr($postedDate, 0, 10), $this->maxDays)) {
            return null;
        }

        $company = '';
        if (isset($item['hiringOrganization']) && is_array($item['hiringOrganization'])) {
            $company = trim((string) ($item['hiringOrganization']['name'] ?? ''));
        }

        $location = '';
        if (isset($item['jobLocation']) && is_array($item['jobLocation'])) {
            $address = $item['jobLocation']['address'] ?? $item['jobLocation'][0]['address'] ?? null;
            if (is_array($address)) {
                $location = implode(', ', array_filter([
                    $address['addressLocality'] ?? null,
                    $address['addressRegion'] ?? null,
                    $address['addressCountry'] ?? null,
                ]));
            }
        }

        $salary = '';
        if (isset($item['baseSalary']) && is_array($item['baseSalary'])) {
            $value = $item['baseSalary']['value'] ?? null;
            if (is_array($value) && isset($value['minValue'], $value['maxValue'])) {
                $symbol = ($value['currency'] ?? 'USD') === 'USD' ? '$' : '';
                $salary = sprintf('%s%s - %s%s', $symbol, $value['minValue'], $symbol, $value['maxValue']);
            }
        }

        return new JobListingData(
            source: 'ZipRecruiter',
            title: $title,
            company: $company,
            location: $location,
            url: $url,
            postedDate: $postedDate !== '' ? substr($postedDate, 0, 10) : null,
            salary: $salary ?: null,
        );
    }

    /**
     * @return JobListingData[]
     */
    private function parseHtmlCards(string $html): array
    {
        $jobs = [];
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//article[contains(@class, 'job_result')]");
        if ($cards === false) {
            return [];
        }

        foreach ($cards as $card) {
            if (! $card instanceof DOMElement) {
                continue;
            }

            $titleNode = $this->queryOne($xpath, ".//h2[contains(@class, 'title')]//a", $card);
            $companyNode = $this->queryOne($xpath, ".//a[contains(@class, 'company') or contains(@class, 'hiring')]", $card)
                ?? $this->queryOne($xpath, ".//p[contains(@class, 'company')]", $card);
            $locationNode = $this->queryOne($xpath, ".//a[contains(@class, 'location') or contains(@class, 'job_location')]", $card);
            $dateNode = $this->queryOne($xpath, './/time', $card);

            $title = trim($titleNode->textContent ?? '');
            $url = $titleNode instanceof DOMElement ? $titleNode->getAttribute('href') : '';
            $postedDate = $dateNode instanceof DOMElement ? $dateNode->getAttribute('datetime') : null;

            if ($title === '' || $url === '' || ! DateFilter::isWithinDays($postedDate, $this->maxDays)) {
                continue;
            }

            if (! str_starts_with($url, 'http')) {
                $url = 'https://www.ziprecruiter.com'.$url;
            }

            $jobs[] = new JobListingData(
                source: 'ZipRecruiter',
                title: $title,
                company: trim($companyNode->textContent ?? ''),
                location: trim($locationNode->textContent ?? ''),
                url: $url,
                postedDate: $postedDate ?: null,
                postedRelative: trim($dateNode->textContent ?? '') ?: null,
            );
        }

        return $jobs;
    }

    private function queryOne(DOMXPath $xpath, string $expression, DOMNode $context): ?DOMNode
    {
        $nodes = $xpath->query($expression, $context);
        $node = $nodes === false ? null : $nodes->item(0);

        return $node instanceof DOMNode ? $node : null;
    }
}
