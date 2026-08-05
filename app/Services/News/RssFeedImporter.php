<?php

namespace App\Services\News;

use App\Models\NewsArticle;
use App\Models\NewsFeed;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class RssFeedImporter
{
    /** @return array{imported: int, updated: int} */
    public function import(NewsFeed $feed): array
    {
        try {
            $response = Http::accept('application/rss+xml, application/atom+xml, application/xml, text/xml')
                ->timeout(20)
                ->get($feed->url)
                ->throw();

            $document = new DOMDocument;
            $previousErrors = libxml_use_internal_errors(true);
            $loaded = $document->loadXML($response->body(), LIBXML_NOCDATA | LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            if (! $loaded) {
                throw new RuntimeException('The URL did not return valid XML.');
            }

            $xpath = new DOMXPath($document);
            $isAtom = strtolower($document->documentElement?->localName ?? '') === 'feed';
            $nodes = $xpath->query($isAtom
                ? '/*[local-name()="feed"]/*[local-name()="entry"]'
                : '//*[local-name()="channel"]/*[local-name()="item"]');

            if ($nodes === false) {
                throw new RuntimeException('The feed entries could not be read.');
            }

            $feedName = $this->firstValue($xpath, $isAtom
                ? '/*[local-name()="feed"]/*[local-name()="title"][1]'
                : '//*[local-name()="channel"]/*[local-name()="title"][1]');
            $siteUrl = $isAtom
                ? $this->atomLink($xpath, $document->documentElement)
                : $this->firstValue($xpath, '//*[local-name()="channel"]/*[local-name()="link"][1]');
            $feedIconUrl = $this->feedIconUrl($xpath, $document, $isAtom, $siteUrl, $feed->icon_url);

            $imported = 0;
            $updated = 0;

            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $article = $this->articleData($xpath, $node, $isAtom);

                if ($article === null) {
                    continue;
                }

                $model = NewsArticle::query()->updateOrCreate(
                    [
                        'news_feed_id' => $feed->id,
                        'guid_hash' => hash('sha256', $article['guid']),
                    ],
                    [
                        'title' => $article['title'],
                        'url' => $article['url'],
                        'image_url' => $article['image_url'],
                        'author' => $article['author'],
                        'summary' => $article['summary'],
                        'published_at' => $article['published_at'],
                    ],
                );

                $model->wasRecentlyCreated ? $imported++ : $updated++;
            }

            $feed->update([
                'name' => $feedName ?: $feed->name,
                'site_url' => $siteUrl ?: $feed->site_url,
                'icon_url' => $feedIconUrl,
                'last_fetched_at' => now(),
                'last_error' => null,
            ]);

            return compact('imported', 'updated');
        } catch (Throwable $exception) {
            $feed->update([
                'last_fetched_at' => now(),
                'last_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array{guid: string, title: string, url: string, image_url: string|null, author: string|null, summary: string|null, published_at: Carbon|null}|null
     */
    private function articleData(DOMXPath $xpath, DOMElement $node, bool $isAtom): ?array
    {
        $title = $this->firstValue($xpath, './*[local-name()="title"][1]', $node);
        $url = $isAtom
            ? $this->atomLink($xpath, $node)
            : $this->firstValue($xpath, './*[local-name()="link"][1]', $node);

        if ($title === null || $url === null) {
            return null;
        }

        $guid = $this->firstValue($xpath, $isAtom
            ? './*[local-name()="id"][1]'
            : './*[local-name()="guid"][1]', $node) ?: $url;
        $date = $this->firstValue(
            $xpath,
            './*[local-name()="pubDate" or local-name()="published" or local-name()="updated" or local-name()="date"][1]',
            $node,
        );
        $summary = $this->firstValue(
            $xpath,
            './*[local-name()="description" or local-name()="summary" or local-name()="content"][1]',
            $node,
        );
        $imageUrl = $this->firstValue(
            $xpath,
            './*[local-name()="enclosure" and starts-with(@type, "image/")][1]/@url'
            .' | .//*[local-name()="content" and (@medium="image" or starts-with(@type, "image/"))][1]/@url'
            .' | .//*[local-name()="thumbnail"][1]/@url',
            $node,
        );

        if ($imageUrl === null && $summary !== null) {
            $imageUrl = $this->firstImageFromHtml($summary, $url);
        }

        return [
            'guid' => $guid,
            'title' => $title,
            'url' => $url,
            'image_url' => $this->absoluteUrl($imageUrl, $url),
            'author' => $this->firstValue(
                $xpath,
                './*[local-name()="author"]/*[local-name()="name"][1] | ./*[local-name()="creator"][1] | ./*[local-name()="author"][1]',
                $node,
            ),
            'summary' => $summary !== null ? trim(strip_tags(html_entity_decode($summary))) : null,
            'published_at' => $this->parseDate($date),
        ];
    }

    private function feedIconUrl(
        DOMXPath $xpath,
        DOMDocument $document,
        bool $isAtom,
        ?string $siteUrl,
        ?string $existingIconUrl,
    ): ?string {
        $feedIconUrl = $this->firstValue($xpath, $isAtom
            ? '/*[local-name()="feed"]/*[local-name()="icon" or local-name()="logo"][1]'
            : '//*[local-name()="channel"]/*[local-name()="image"]/*[local-name()="url"][1]');

        if ($feedIconUrl !== null) {
            return $this->absoluteUrl($feedIconUrl, $siteUrl);
        }

        if ($existingIconUrl !== null || $siteUrl === null) {
            return $existingIconUrl;
        }

        try {
            $response = Http::timeout(10)->get($siteUrl)->throw();
            $siteDocument = new DOMDocument;
            $previousErrors = libxml_use_internal_errors(true);
            $loaded = $siteDocument->loadHTML($response->body(), LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            if ($loaded) {
                $siteXpath = new DOMXPath($siteDocument);
                $icon = $this->firstValue(
                    $siteXpath,
                    '//link[contains(concat(" ", translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), " "), " icon ")][1]/@href',
                );

                if ($icon !== null) {
                    return $this->absoluteUrl($icon, $siteUrl);
                }
            }
        } catch (Throwable) {
            // A missing site icon should never prevent article imports.
        }

        $parts = parse_url($siteUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $faviconUrl = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').'/favicon.ico';

        try {
            $response = Http::timeout(10)->head($faviconUrl);

            return $response->successful() && str_starts_with(strtolower($response->header('Content-Type')), 'image/')
                ? $faviconUrl
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function firstImageFromHtml(string $html, string $baseUrl): ?string
    {
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML('<meta charset="utf-8"><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if (! $loaded) {
            return null;
        }

        $image = (new DOMXPath($document))->query('//img[@src][1]')?->item(0);

        return $image instanceof DOMElement
            ? $this->absoluteUrl($image->getAttribute('src'), $baseUrl)
            : null;
    }

    private function absoluteUrl(?string $url, ?string $baseUrl): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return $url;
        }

        if ($baseUrl === null) {
            return null;
        }

        $parts = parse_url($baseUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        if (str_starts_with($url, '//')) {
            return $parts['scheme'].':'.$url;
        }

        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }

        $path = isset($parts['path']) ? rtrim(dirname($parts['path']), '/') : '';

        return $origin.$path.'/'.$url;
    }

    private function atomLink(DOMXPath $xpath, ?DOMNode $context): ?string
    {
        if ($context === null) {
            return null;
        }

        $links = $xpath->query('./*[local-name()="link"]', $context);

        if ($links === false) {
            return null;
        }

        foreach ($links as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $rel = $link->getAttribute('rel');
            $href = trim($link->getAttribute('href'));

            if ($href !== '' && ($rel === '' || $rel === 'alternate')) {
                return $href;
            }
        }

        return null;
    }

    private function firstValue(DOMXPath $xpath, string $query, ?DOMNode $context = null): ?string
    {
        $nodes = $xpath->query($query, $context);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->textContent);

        return $value !== '' ? $value : null;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
