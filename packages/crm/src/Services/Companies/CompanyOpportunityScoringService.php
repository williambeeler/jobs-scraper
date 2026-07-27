<?php

namespace WilliamBeeler\Crm\Services\Companies;

use Illuminate\Support\Facades\Http;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use WilliamBeeler\Crm\Models\Company;

class CompanyOpportunityScoringService
{
    /**
     * Website hosts that are not considered a valid custom-domain "main website".
     *
     * @var array<int, string>
     */
    private const DISALLOWED_HOST_SUFFIXES = [
        'facebook.com',
        'fb.com',
        'instagram.com',
        'x.com',
        'twitter.com',
        'linkedin.com',
        'wixsite.com',
        'wordpress.com',
        'weebly.com',
        'squarespace.com',
        'blogspot.com',
        'tumblr.com',
        'yelp.com',
        'linktr.ee',
        'tiktok.com',
    ];

    private const RUBRIC_PATH = 'prompts/opportunity-score-rubric.md';

    /**
     * @return array{
     *   score: int,
     *   summary: string,
     *   website: string|null,
     *   auto_zero: bool
     * }
     */
    public function scoreCompany(
        Company $company,
        string $provider = 'openai',
        string $model = 'gpt-4.1-mini',
        bool $persist = true
    ): array {
        $website = $this->normalizeWebsite($company->website);

        if (! $this->hasQualifyingWebsite($website)) {
            $summary = 'Opportunity score set to 100: no qualifying primary website (custom domain required).';
            if ($persist) {
                $this->persistScore($company, 100, $summary, $website, true, $provider, $model);
            }

            return [
                'score' => 100,
                'summary' => $summary,
                'website' => $website,
                'auto_zero' => true,
            ];
        }

        $websiteSnapshot = $this->fetchWebsiteSnapshot($website);
        $rubric = $this->loadRubricMarkdown();
        $aiScore = $this->requestScoreFromAi($website, $websiteSnapshot, $rubric, $provider, $model);
        $score = max(0, min(100, (int) ($aiScore['opportunity_score'] ?? 0)));
        $summary = trim($aiScore['summary']) !== '' ? trim($aiScore['summary']) : 'Scored from website analysis.';

        if ($persist) {
            $this->persistScore($company, $score, $summary, $website, false, $provider, $model);
        }

        return [
            'score' => $score,
            'summary' => $summary,
            'website' => $website,
            'auto_zero' => false,
        ];
    }

    public function hasQualifyingWebsite(?string $website): bool
    {
        if (! $website) {
            return false;
        }

        $host = strtolower((string) parse_url($website, PHP_URL_HOST));
        if ($host === '' || str_contains($host, 'localhost')) {
            return false;
        }

        foreach (self::DISALLOWED_HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return false;
            }
        }

        // Basic custom-domain sanity check.
        return str_contains($host, '.');
    }

    private function normalizeWebsite(?string $website): ?string
    {
        $candidate = trim((string) $website);
        if ($candidate === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $candidate)) {
            $candidate = 'https://'.ltrim($candidate, '/');
        }

        return filter_var($candidate, FILTER_VALIDATE_URL) ? $candidate : null;
    }

    private function fetchWebsiteSnapshot(string $website): string
    {
        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'CRM Opportunity Scorer/1.0'])
            ->get($website);

        if (! $response->successful()) {
            throw new \RuntimeException("Could not fetch website content for scoring ({$response->status()}).");
        }

        $body = (string) $response->body();
        $body = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $body) ?? $body;
        $body = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $body) ?? $body;
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags($body)));

        if ($text === '') {
            throw new \RuntimeException('Website content was empty after text extraction.');
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 12000);
        }

        return substr($text, 0, 12000);
    }

    private function loadRubricMarkdown(): string
    {
        $path = dirname(__DIR__, 3).'/resources/'.self::RUBRIC_PATH;
        if (! is_file($path)) {
            return <<<'MD'
# Opportunity Score Rubric (Placeholder)
- Score from 0-100.
- Use website clarity, offer quality, trust signals, and conversion-readiness.
- Prefer concise, evidence-based notes.
MD;
        }

        $content = trim((string) file_get_contents($path));

        return $content !== '' ? $content : "# Opportunity Score Rubric\n\nPlaceholder rubric file is empty.";
    }

    /**
     * @return array{opportunity_score: int, summary: string}
     */
    private function requestScoreFromAi(
        string $website,
        string $websiteSnapshot,
        string $rubricMarkdown,
        string $provider,
        string $model
    ): array {
        $schema = new ObjectSchema(
            'opportunity_score',
            'Opportunity scoring output.',
            [
                new StringSchema('opportunity_score', 'Integer 0-100 opportunity score.', false),
                new StringSchema('summary', 'Short explanation (1-3 sentences) tied to rubric.', false),
            ],
            requiredFields: ['opportunity_score', 'summary']
        );

        $prompt = sprintf(
            "Score this company website using the rubric below.\n\n".
            "RUBRIC (markdown):\n%s\n\n".
            "WEBSITE URL:\n%s\n\n".
            "WEBSITE TEXT SNAPSHOT:\n%s",
            $rubricMarkdown,
            $website,
            $websiteSnapshot
        );

        $response = Prism::structured()
            ->using($provider, $model)
            ->withSystemPrompt('You are a strict B2B opportunity scoring analyst. Follow the rubric exactly.')
            ->withSchema($schema)
            ->withPrompt($prompt)
            ->asStructured();

        $structured = is_array($response->structured) ? $response->structured : [];
        $rawScore = trim((string) ($structured['opportunity_score'] ?? '0'));
        $score = is_numeric($rawScore) ? (int) $rawScore : 0;
        $summary = trim((string) ($structured['summary'] ?? ''));

        return [
            'opportunity_score' => $score,
            'summary' => $summary,
        ];
    }

    private function persistScore(
        Company $company,
        int $score,
        string $summary,
        ?string $website,
        bool $autoZero,
        string $provider,
        string $model
    ): void {
        $company->opportunity_score_index = $score;
        $company->opportunity_summary = $summary;

        $meta = is_array($company->meta) ? $company->meta : [];
        $ranAt = now()->toIso8601String();
        $meta['opportunity_scoring'] = [
            'provider' => $provider,
            'model' => $model,
            'ran_at' => $ranAt,
            'website' => $website,
            'auto_zero' => $autoZero,
            'score' => $score,
            'summary' => $summary,
        ];

        $history = $meta['opportunity_scoring_history'] ?? [];
        if (! is_array($history)) {
            $history = [];
        }

        $history[] = [
            'ran_at' => $ranAt,
            'score' => $score,
            'summary' => $summary,
            'website' => $website,
            'auto_zero' => $autoZero,
            'provider' => $provider,
            'model' => $model,
        ];
        $meta['opportunity_scoring_history'] = array_slice($history, -50);

        $company->meta = $meta;
        $company->save();
    }
}
