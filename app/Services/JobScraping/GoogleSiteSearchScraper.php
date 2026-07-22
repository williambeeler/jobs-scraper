<?php

namespace App\Services\JobScraping;

use App\Data\JobListingData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

abstract class GoogleSiteSearchScraper implements JobSourceScraper
{
    public function __construct(
        protected readonly HttpClient $http,
        protected readonly int $maxDays,
        private readonly int $googleMaxPages = 2,
    ) {}

    final public function scrape(string $keywords): array
    {
        $nativeResult = $this->scrapeNative($keywords);
        if ($nativeResult['error'] === null) {
            return $nativeResult;
        }

        $googleResult = $this->scrapeGoogle($keywords);
        if ($googleResult['jobs'] !== []) {
            return $googleResult;
        }

        return [
            'jobs' => $nativeResult['jobs'],
            'error' => $nativeResult['error'].' '.$googleResult['error'],
        ];
    }

    /**
     * @return array{jobs: JobListingData[], error: string|null}
     */
    abstract protected function scrapeNative(string $keywords): array;

    abstract protected function sourceName(): string;

    abstract protected function googleSite(): string;

    abstract protected function acceptsGoogleUrl(string $url): bool;

    protected function normalizeGoogleUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
    }

    /**
     * @return array{jobs: JobListingData[], error: string|null}
     */
    private function scrapeGoogle(string $keywords): array
    {
        $jobs = [];
        $seenUrls = [];
        $error = 'Google site search fallback returned no listings.';
        $oldestDate = date('Y-m-d', strtotime("-{$this->maxDays} days"));
        $query = rawurlencode(trim($keywords).' remote site:'.$this->googleSite().' after:'.$oldestDate);

        for ($page = 0; $page < $this->googleMaxPages; $page++) {
            $start = $page * 10;
            $html = $this->http->get("https://www.google.com/search?q={$query}&start={$start}&filter=0");
            if ($html === null) {
                $error = 'Google site search fallback request failed.';
                break;
            }

            if ($this->isBlockedGooglePage($html)) {
                $error = 'Google site search fallback returned a security or JavaScript interstitial.';
                break;
            }

            $pageJobs = $this->parseGoogleResults($html);
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

        return ['jobs' => $jobs, 'error' => $jobs === [] ? $error : null];
    }

    /**
     * @return JobListingData[]
     */
    private function parseGoogleResults(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a[@href][.//h3]');
        if ($links === false) {
            return [];
        }

        $jobs = [];
        foreach ($links as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $url = $this->googleTargetUrl(html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5));
            if ($url === null || ! $this->acceptsGoogleUrl($url)) {
                continue;
            }

            $titleNode = $xpath->query('.//h3', $link)?->item(0);
            $title = $this->cleanGoogleTitle($this->nodeText($titleNode));
            if ($title === '') {
                continue;
            }

            $jobs[] = new JobListingData(
                source: $this->sourceName(),
                title: $title,
                company: '',
                location: 'Remote',
                url: $this->normalizeGoogleUrl($url),
            );
        }

        return $jobs;
    }

    private function googleTargetUrl(string $href): ?string
    {
        if (str_starts_with($href, 'https://') || str_starts_with($href, 'http://')) {
            $host = strtolower((string) parse_url($href, PHP_URL_HOST));
            $path = parse_url($href, PHP_URL_PATH);
            if (($host === 'google.com' || str_ends_with($host, '.google.com')) && $path === '/url') {
                return $this->googleRedirectTarget($href);
            }

            return $href;
        }

        if (! str_starts_with($href, '/url?')) {
            return null;
        }

        return $this->googleRedirectTarget($href);
    }

    private function googleRedirectTarget(string $url): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);
        $target = $parameters['q'] ?? $parameters['url'] ?? null;

        return is_string($target) && str_starts_with($target, 'http') ? $target : null;
    }

    private function cleanGoogleTitle(string $title): string
    {
        return trim((string) preg_replace(
            '/\s+(?:[-|]\s*)?(?:Indeed(?:\.com)?|ZipRecruiter)$/i',
            '',
            $title,
        ));
    }

    private function nodeText(?DOMNode $node): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $node?->textContent ?? ''));
    }

    private function isBlockedGooglePage(string $html): bool
    {
        return str_contains($html, 'Our systems have detected unusual traffic')
            || str_contains($html, '/sorry/')
            || str_contains($html, 'emsg=SG_REL');
    }
}
