#!/usr/bin/env php
<?php
/**
 * CLI job scraper for LinkedIn, Indeed, and ZipRecruiter.
 *
 * Usage:
 *   php scrape_jobs.php
 *   php scrape_jobs.php --keywords "wordpress remote" --days 5
 *   php scrape_jobs.php --source linkedin --json
 *   php scrape_jobs.php --verbose
 */

declare(strict_types=1);

final class JobListing
{
    public function __construct(
        public readonly string $source,
        public readonly string $title,
        public readonly string $company,
        public readonly string $location,
        public readonly string $url,
        public readonly ?string $postedDate = null,
        public readonly ?string $postedRelative = null,
        public readonly ?string $salary = null,
    ) {}
}

final class HttpClient
{
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';

    public function __construct(
        private readonly bool $verbose = false,
        private readonly int $delayMs = 1500,
    ) {}

    public function get(string $url, array $extraHeaders = []): ?string
    {
        usleep($this->delayMs * 1000);

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $headers = array_merge([
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Cache-Control: no-cache',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($this->verbose) {
            fwrite(STDERR, sprintf("[HTTP %d] %s\n", $status, $url));
            if ($error !== '') {
                fwrite(STDERR, "  cURL error: {$error}\n");
            }
        }

        if ($body === false || $status < 200 || $status >= 400) {
            return null;
        }

        return $body;
    }
}

final class DateFilter
{
    public static function isWithinDays(?string $isoDate, int $maxDays): bool
    {
        if ($isoDate === null || $isoDate === '') {
            return true;
        }

        $posted = strtotime($isoDate);
        if ($posted === false) {
            return true;
        }

        $cutoff = strtotime(sprintf('-%d days', $maxDays));
        return $posted >= $cutoff;
    }

    public static function isTimestampWithinDays(?int $timestampMs, int $maxDays): bool
    {
        if ($timestampMs === null) {
            return true;
        }

        $posted = (int) floor($timestampMs / 1000);
        $cutoff = strtotime(sprintf('-%d days', $maxDays));
        return $posted >= $cutoff;
    }
}

final class LinkedInScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
        private readonly int $maxPages = 3,
    ) {}

    /**
     * @return array{jobs: JobListing[], error: ?string}
     */
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
                : "https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search?keywords={$encodedKeywords}&f_TPR=r{$seconds}&sortBy=DD&start=" . ($page * 25);

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
     * @return JobListing[]
     */
    private function parseJobCards(string $html): array
    {
        $jobs = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//div[contains(@class, 'job-search-card')]");

        if ($cards === false) {
            return [];
        }

        foreach ($cards as $card) {
            if (!$card instanceof DOMElement) {
                continue;
            }

            $linkNode = $xpath->query(".//a[contains(@class, 'base-card__full-link')]", $card)?->item(0);
            $titleNode = $xpath->query(".//h3[contains(@class, 'base-search-card__title')]", $card)?->item(0);
            $companyNode = $xpath->query(".//h4[contains(@class, 'base-search-card__subtitle')]//a", $card)?->item(0)
                ?? $xpath->query(".//h4[contains(@class, 'base-search-card__subtitle')]", $card)?->item(0);
            $locationNode = $xpath->query(".//span[contains(@class, 'job-search-card__location')]", $card)?->item(0);
            $dateNode = $xpath->query(".//time[contains(@class, 'job-search-card__listdate')]", $card)?->item(0);

            $url = $linkNode instanceof DOMElement ? html_entity_decode($linkNode->getAttribute('href'), ENT_QUOTES | ENT_HTML5) : '';
            $title = $this->nodeText($titleNode);
            $company = $this->nodeText($companyNode);
            $location = $this->nodeText($locationNode);
            $postedDate = $dateNode instanceof DOMElement ? $dateNode->getAttribute('datetime') : null;
            $postedRelative = $this->nodeText($dateNode);

            if ($title === '' || $url === '') {
                continue;
            }

            if (!DateFilter::isWithinDays($postedDate, $this->maxDays)) {
                continue;
            }

            $jobs[] = new JobListing(
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

    private function cleanUrl(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $clean = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
        if (isset($parts['path'])) {
            $clean .= $parts['path'];
        }

        return $clean;
    }
}

final class IndeedScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    /**
     * @return array{jobs: JobListing[], error: ?string}
     */
    public function scrape(string $keywords): array
    {
        $query = rawurlencode($keywords);
        $url = "https://www.indeed.com/jobs?q={$query}&l=remote&fromage={$this->maxDays}&sort=date&radius=0";

        $html = $this->http->get($url, ['Referer: https://www.indeed.com/']);
        if ($html === null) {
            return [
                'jobs' => [],
                'error' => 'Indeed blocked the request (Cloudflare protection). Try again later or from a residential network.',
            ];
        }

        if ($this->isBlockedPage($html)) {
            return [
                'jobs' => [],
                'error' => 'Indeed returned a security challenge page. Automated scraping is blocked without a real browser session.',
            ];
        }

        $results = $this->extractMosaicResults($html);
        if ($results === []) {
            $results = $this->extractInitialDataResults($html);
        }

        if ($results === []) {
            return [
                'jobs' => [],
                'error' => 'Indeed page loaded but no job listings were found in the response.',
            ];
        }

        $jobs = [];
        foreach ($results as $result) {
            $job = $this->mapIndeedResult($result);
            if ($job !== null) {
                $jobs[] = $job;
            }
        }

        return ['jobs' => $jobs, 'error' => null];
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
        if (!preg_match('/window\.mosaic\.providerData\["mosaic-provider-jobcards"\]\s*=\s*(\{.+?\});/s', $html, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);
        if (!is_array($data)) {
            return [];
        }

        return $data['metaData']['mosaicProviderJobCardsModel']['results'] ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractInitialDataResults(string $html): array
    {
        if (!preg_match('/window\._initialData\s*=\s*(\{.+?\});/s', $html, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);
        if (!is_array($data)) {
            return [];
        }

        $candidates = [
            $data['mosaicProviderJobCardsModel']['results'] ?? null,
            $data['jobCards']['results'] ?? null,
            $data['searchTitleBarModel']['results'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function mapIndeedResult(array $result): ?JobListing
    {
        $title = trim((string) ($result['title'] ?? $result['displayTitle'] ?? ''));
        $jobKey = trim((string) ($result['jobkey'] ?? ''));
        if ($title === '' || $jobKey === '') {
            return null;
        }

        $pubDate = isset($result['pubDate']) ? (int) $result['pubDate'] : null;
        if (!DateFilter::isTimestampWithinDays($pubDate, $this->maxDays)) {
            return null;
        }

        $postedDate = $pubDate !== null ? date('Y-m-d', (int) floor($pubDate / 1000)) : null;
        $company = trim((string) ($result['company'] ?? $result['truncatedCompany'] ?? ''));
        $location = trim((string) ($result['formattedLocation'] ?? ''));
        $relative = trim((string) ($result['formattedRelativeTime'] ?? ''));
        $salary = trim((string) ($result['salarySnippet']['salaryText'] ?? ''));

        $url = 'https://www.indeed.com/viewjob?jk=' . rawurlencode($jobKey);
        if (isset($result['viewJobLink']) && is_string($result['viewJobLink']) && str_starts_with($result['viewJobLink'], 'http')) {
            $url = $result['viewJobLink'];
        }

        return new JobListing(
            source: 'Indeed',
            title: $title,
            company: $company,
            location: $location,
            url: $url,
            postedDate: $postedDate,
            postedRelative: $relative ?: null,
            salary: $salary ?: null,
        );
    }
}

final class ZipRecruiterScraper
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly int $maxDays,
    ) {}

    /**
     * @return array{jobs: JobListing[], error: ?string}
     */
    public function scrape(string $keywords): array
    {
        $query = rawurlencode($keywords);
        $url = "https://www.ziprecruiter.com/jobs-search?search={$query}&location=remote&days={$this->maxDays}";

        $html = $this->http->get($url);
        if ($html === null) {
            return [
                'jobs' => [],
                'error' => 'ZipRecruiter blocked the request (HTTP error).',
            ];
        }

        if ($this->isBlockedPage($html)) {
            return [
                'jobs' => [],
                'error' => 'ZipRecruiter returned a Cloudflare challenge page. Automated scraping is blocked without a real browser session.',
            ];
        }

        $jobs = $this->parseJsonLd($html);
        if ($jobs === []) {
            $jobs = $this->parseHtmlCards($html);
        }

        if ($jobs === []) {
            return [
                'jobs' => [],
                'error' => 'ZipRecruiter page loaded but no job listings were found in the response.',
            ];
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
     * @return JobListing[]
     */
    private function parseJsonLd(string $html): array
    {
        $jobs = [];
        if (!preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true);
            if (!is_array($decoded)) {
                continue;
            }

            $items = isset($decoded['@type']) ? [$decoded] : $decoded;
            foreach ($items as $item) {
                if (!is_array($item) || ($item['@type'] ?? '') !== 'JobPosting') {
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
     * @param array<string, mixed> $item
     */
    private function mapJsonLdJob(array $item): ?JobListing
    {
        $title = trim((string) ($item['title'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        if ($title === '' || $url === '') {
            return null;
        }

        $postedDate = trim((string) ($item['datePosted'] ?? ''));
        if ($postedDate !== '' && !DateFilter::isWithinDays(substr($postedDate, 0, 10), $this->maxDays)) {
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
                $parts = array_filter([
                    $address['addressLocality'] ?? null,
                    $address['addressRegion'] ?? null,
                    $address['addressCountry'] ?? null,
                ]);
                $location = implode(', ', $parts);
            }
        }

        $salary = '';
        if (isset($item['baseSalary']) && is_array($item['baseSalary'])) {
            $value = $item['baseSalary']['value'] ?? null;
            if (is_array($value)) {
                $min = $value['minValue'] ?? null;
                $max = $value['maxValue'] ?? null;
                $currency = $value['currency'] ?? 'USD';
                if ($min !== null && $max !== null) {
                    $salary = sprintf('%s%s - %s%s', $currency === 'USD' ? '$' : '', $min, $currency === 'USD' ? '$' : '', $max);
                }
            }
        }

        return new JobListing(
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
     * @return JobListing[]
     */
    private function parseHtmlCards(string $html): array
    {
        $jobs = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query("//article[contains(@class, 'job_result')]");

        if ($cards === false) {
            return [];
        }

        foreach ($cards as $card) {
            if (!$card instanceof DOMElement) {
                continue;
            }

            $titleNode = $xpath->query(".//h2[contains(@class, 'title')]//a", $card)?->item(0);
            $companyNode = $xpath->query(".//a[contains(@class, 'company') or contains(@class, 'hiring')]", $card)?->item(0)
                ?? $xpath->query(".//p[contains(@class, 'company')]", $card)?->item(0);
            $locationNode = $xpath->query(".//a[contains(@class, 'location') or contains(@class, 'job_location')]", $card)?->item(0);
            $dateNode = $xpath->query(".//time", $card)?->item(0);

            $title = trim($titleNode?->textContent ?? '');
            $url = $titleNode instanceof DOMElement ? $titleNode->getAttribute('href') : '';
            $company = trim($companyNode?->textContent ?? '');
            $location = trim($locationNode?->textContent ?? '');
            $postedDate = $dateNode instanceof DOMElement ? $dateNode->getAttribute('datetime') : null;
            $postedRelative = trim($dateNode?->textContent ?? '');

            if ($title === '' || $url === '') {
                continue;
            }

            if (!str_starts_with($url, 'http')) {
                $url = 'https://www.ziprecruiter.com' . $url;
            }

            if (!DateFilter::isWithinDays($postedDate, $this->maxDays)) {
                continue;
            }

            $jobs[] = new JobListing(
                source: 'ZipRecruiter',
                title: $title,
                company: $company,
                location: $location,
                url: $url,
                postedDate: $postedDate ?: null,
                postedRelative: $postedRelative ?: null,
            );
        }

        return $jobs;
    }
}

final class CliOptions
{
    /**
     * @return array{
     *   keywords: string,
     *   days: int,
     *   sources: string[],
     *   json: bool,
     *   verbose: bool,
     *   help: bool
     * }
     */
    public static function parse(array $argv): array
    {
        $options = [
            'keywords' => 'wordpress remote',
            'days' => 5,
            'sources' => ['linkedin', 'indeed', 'ziprecruiter'],
            'json' => false,
            'verbose' => false,
            'help' => false,
        ];

        $args = array_slice($argv, 1);
        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            switch ($arg) {
                case '--help':
                case '-h':
                    $options['help'] = true;
                    break;
                case '--json':
                    $options['json'] = true;
                    break;
                case '--verbose':
                case '-v':
                    $options['verbose'] = true;
                    break;
                case '--keywords':
                case '-k':
                    $options['keywords'] = $args[++$i] ?? $options['keywords'];
                    break;
                case '--days':
                case '-d':
                    $options['days'] = max(1, (int) ($args[++$i] ?? $options['days']));
                    break;
                case '--source':
                case '-s':
                    $raw = strtolower($args[++$i] ?? '');
                    $options['sources'] = array_values(array_filter(array_map('trim', explode(',', $raw))));
                    break;
            }
        }

        return $options;
    }

    public static function usage(): string
    {
        return <<<TXT
WordPress Remote Job Scraper

Usage:
  php scrape_jobs.php [options]

Options:
  -k, --keywords <text>   Search keywords (default: "wordpress remote")
  -d, --days <number>     Max age in days (default: 5)
  -s, --source <list>     Comma-separated sources: linkedin,indeed,ziprecruiter
  --json                  Output JSON instead of plain text
  -v, --verbose           Show HTTP request details on stderr
  -h, --help              Show this help message

Examples:
  php scrape_jobs.php
  php scrape_jobs.php --source linkedin --json
  php scrape_jobs.php --keywords "wordpress developer remote" --days 3

TXT;
    }
}

/**
 * @param JobListing[] $jobs
 */
function renderText(array $jobs, array $errors, string $keywords, int $days): void
{
    echo str_repeat('=', 72) . PHP_EOL;
    echo "Job Search: {$keywords}" . PHP_EOL;
    echo "Posted within: {$days} days" . PHP_EOL;
    echo str_repeat('=', 72) . PHP_EOL . PHP_EOL;

    if ($jobs === []) {
        echo "No jobs found.\n\n";
    } else {
        $grouped = [];
        foreach ($jobs as $job) {
            $grouped[$job->source][] = $job;
        }

        foreach ($grouped as $source => $sourceJobs) {
            echo strtoupper($source) . ' (' . count($sourceJobs) . ' results)' . PHP_EOL;
            echo str_repeat('-', 72) . PHP_EOL;

            foreach ($sourceJobs as $index => $job) {
                $posted = $job->postedRelative ?? $job->postedDate ?? 'Unknown date';
                echo ($index + 1) . ". {$job->title}" . PHP_EOL;
                echo "   Company:  {$job->company}" . PHP_EOL;
                echo "   Location: {$job->location}" . PHP_EOL;
                echo "   Posted:   {$posted}" . PHP_EOL;
                if ($job->salary) {
                    echo "   Salary:   {$job->salary}" . PHP_EOL;
                }
                echo "   URL:      {$job->url}" . PHP_EOL . PHP_EOL;
            }
        }
    }

    if ($errors !== []) {
        echo str_repeat('-', 72) . PHP_EOL;
        echo "Warnings:" . PHP_EOL;
        foreach ($errors as $source => $message) {
            echo "  [{$source}] {$message}" . PHP_EOL;
        }
        echo PHP_EOL;
    }

    echo 'Total jobs: ' . count($jobs) . PHP_EOL;
}

/**
 * @param JobListing[] $jobs
 */
function renderJson(array $jobs, array $errors, string $keywords, int $days): void
{
    $payload = [
        'query' => [
            'keywords' => $keywords,
            'max_days' => $days,
        ],
        'total' => count($jobs),
        'jobs' => array_map(static fn (JobListing $job): array => [
            'source' => $job->source,
            'title' => $job->title,
            'company' => $job->company,
            'location' => $job->location,
            'url' => $job->url,
            'posted_date' => $job->postedDate,
            'posted_relative' => $job->postedRelative,
            'salary' => $job->salary,
        ], $jobs),
        'errors' => $errors,
    ];

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function main(array $argv): int
{
    $options = CliOptions::parse($argv);
    if ($options['help']) {
        echo CliOptions::usage();
        return 0;
    }

    $http = new HttpClient(verbose: $options['verbose']);
    $allJobs = [];
    $errors = [];

    $scrapers = [
        'linkedin' => new LinkedInScraper($http, $options['days']),
        'indeed' => new IndeedScraper($http, $options['days']),
        'ziprecruiter' => new ZipRecruiterScraper($http, $options['days']),
    ];

    foreach ($options['sources'] as $source) {
        if (!isset($scrapers[$source])) {
            $errors[$source] = 'Unknown source. Use linkedin, indeed, or ziprecruiter.';
            continue;
        }

        fwrite(STDERR, "Scraping {$source}...\n");
        $result = $scrapers[$source]->scrape($options['keywords']);
        $allJobs = array_merge($allJobs, $result['jobs']);

        if ($result['error'] !== null) {
            $errors[$source] = $result['error'];
        }
    }

    usort($allJobs, static function (JobListing $a, JobListing $b): int {
        return strcmp($b->postedDate ?? '', $a->postedDate ?? '');
    });

    if ($options['json']) {
        renderJson($allJobs, $errors, $options['keywords'], $options['days']);
    } else {
        renderText($allJobs, $errors, $options['keywords'], $options['days']);
    }

    return $errors !== [] && $allJobs === [] ? 1 : 0;
}

exit(main($argv));
