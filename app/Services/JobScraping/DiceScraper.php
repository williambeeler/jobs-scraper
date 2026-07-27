<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class DiceScraper implements JobSourceScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    public function scrape(string $keywords): array
    {
        $slug = str_replace('%20', '+', rawurlencode(trim($keywords)));
        $html = $this->http->get("https://www.dice.com/jobs/q-{$slug}-jobs");

        if ($html === null) {
            return ['jobs' => [], 'error' => 'Dice request failed or was blocked.'];
        }

        $jobs = $this->parseJobCards($html);

        return [
            'jobs' => $jobs,
            'error' => $jobs === [] ? 'Dice page loaded but no recent remote job listings were found.' : null,
        ];
    }

    /**
     * @return JobListingData[]
     */
    private function parseJobCards(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $links = $xpath->query("//a[@data-testid='job-search-job-detail-link']");
        if ($links === false) {
            return [];
        }

        $jobs = [];
        $seenUrls = [];

        foreach ($links as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $card = $this->queryOne(
                $xpath,
                "ancestor::div[.//a[@data-testid='job-search-job-card-link']][1]",
                $link,
            );
            if ($card === null) {
                continue;
            }

            $title = $this->nodeText($link);
            $path = html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5);
            $companyNode = $this->queryOne($xpath, ".//a[contains(@href, '/company-profile/')]", $card);
            $metadata = $this->metadata($xpath, $card);

            if (
                $title === ''
                || ! preg_match('#^/job-detail/[a-z0-9-]+$#i', $path)
                || ! str_contains(mb_strtolower($metadata['location']), 'remote')
                || ! $this->isWithinDays($metadata['postedRelative'])
            ) {
                continue;
            }

            $url = 'https://www.dice.com'.$path;
            if (isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;
            $jobs[] = new JobListingData(
                source: 'Dice',
                title: $title,
                company: $this->nodeText($companyNode),
                location: $metadata['location'],
                url: $url,
                postedRelative: $metadata['postedRelative'],
            );
        }

        return $jobs;
    }

    /**
     * @return array{location: string, postedRelative: string}
     */
    private function metadata(DOMXPath $xpath, DOMNode $card): array
    {
        $paragraphs = $xpath->query(".//div[contains(@class, 'content')]//p", $card);
        $values = [];

        if ($paragraphs !== false) {
            foreach ($paragraphs as $paragraph) {
                $text = $this->nodeText($paragraph);
                if ($text !== '') {
                    $values[] = $text;
                }
            }
        }

        $relativeIndex = null;
        foreach ($values as $index => $value) {
            if (preg_match('/^(?:today|yesterday|\d+\s*[mhdw]\s+ago)$/i', $value)) {
                $relativeIndex = $index;
                break;
            }
        }

        return [
            'location' => $relativeIndex !== null && $relativeIndex > 0 ? $values[$relativeIndex - 1] : '',
            'postedRelative' => $relativeIndex !== null ? $values[$relativeIndex] : '',
        ];
    }

    private function isWithinDays(string $relative): bool
    {
        if ($relative === '' || preg_match('/^(?:today|yesterday|\d+\s*[mh]\s+ago)$/i', $relative)) {
            return true;
        }

        if (preg_match('/^(\d+)\s*d\s+ago$/i', $relative, $matches)) {
            return (int) $matches[1] <= $this->maxDays;
        }

        if (preg_match('/^(\d+)\s*w\s+ago$/i', $relative, $matches)) {
            return ((int) $matches[1] * 7) <= $this->maxDays;
        }

        return false;
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
