<?php

namespace WilliamBeeler\Crm\Support;

class CompanyListPdf
{
    private const PAGE_WIDTH = 612.0;
    private const PAGE_HEIGHT = 792.0;
    private const MARGIN_LEFT = 40.0;
    private const MARGIN_TOP = 40.0;
    private const MARGIN_BOTTOM = 40.0;
    private const CONTENT_WIDTH = self::PAGE_WIDTH - (self::MARGIN_LEFT * 2);

    /**
     * @param array<int, array{name?: string, address_line1?: string, address_line2?: string, city?: string, state?: string, postal_code?: string, country?: string, website?: string, phone?: string, email?: string}> $entries
     */
    public static function generate(array $entries, string $title = 'Companies'): string
    {
        $pages = [];
        $commands = [];
        $cursorY = self::MARGIN_TOP;

        $startPage = function () use (&$commands, &$cursorY, $title): void {
            $commands = [];
            $cursorY = self::MARGIN_TOP;
            $commands[] = self::textCommand(self::MARGIN_LEFT, self::toPdfY($cursorY), 16, $title);
            $cursorY += 24;
        };

        $flushPage = function () use (&$pages, &$commands): void {
            $pages[] = implode("\n", $commands) . "\n";
        };

        $ensureRoom = function (float $height) use (&$cursorY, &$flushPage, &$startPage): void {
            if (($cursorY + $height) > (self::PAGE_HEIGHT - self::MARGIN_BOTTOM)) {
                $flushPage();
                $startPage();
            }
        };

        $startPage();

        foreach ($entries as $entry) {
            $name = trim((string) ($entry['name'] ?? ''));
            $addressLine1 = trim((string) ($entry['address_line1'] ?? ''));
            $addressLine2 = trim((string) ($entry['address_line2'] ?? ''));
            $cityStatePostal = trim(implode(' ', array_filter([
                trim(implode(', ', array_filter([
                    trim((string) ($entry['city'] ?? '')),
                    trim((string) ($entry['state'] ?? '')),
                ]))),
                trim((string) ($entry['postal_code'] ?? '')),
            ])));
            $country = trim((string) ($entry['country'] ?? ''));
            $website = trim((string) ($entry['website'] ?? ''));
            $phone = trim((string) ($entry['phone'] ?? ''));
            $email = trim((string) ($entry['email'] ?? ''));

            $details = array_values(array_filter([
                $addressLine1,
                $addressLine2,
                trim(implode(', ', array_filter([$cityStatePostal, $country]))),
                $website !== '' ? 'Website: ' . $website : '',
                $phone !== '' ? 'Phone: ' . $phone : '',
                $email !== '' ? 'Email: ' . $email : '',
            ], static fn (string $line): bool => $line !== ''));

            $blockHeight = 24 + (count($details) * 14) + 10;
            $ensureRoom($blockHeight);

            $commands[] = self::textCommand(self::MARGIN_LEFT, self::toPdfY($cursorY), 12.5, self::truncate($name, 96));
            $cursorY += 18;

            foreach ($details as $detail) {
                $commands[] = self::textCommand(
                    self::MARGIN_LEFT + 12,
                    self::toPdfY($cursorY),
                    10.5,
                    self::truncate($detail, 120)
                );
                $cursorY += 14;
            }

            $commands[] = self::lineCommand(
                self::MARGIN_LEFT,
                self::toPdfY($cursorY),
                self::MARGIN_LEFT + self::CONTENT_WIDTH,
                self::toPdfY($cursorY)
            );
            $cursorY += 10;
        }

        $flushPage();

        return self::buildPdf($pages);
    }

    /**
     * @param array<int, string> $pageContents
     */
    private static function buildPdf(array $pageContents): string
    {
        if (empty($pageContents)) {
            $pageContents = ["\n"];
        }

        $catalogObjectId = 1;
        $pagesObjectId = 2;
        $nextObjectId = 3;
        $pageObjectIds = [];
        $contentObjectIds = [];

        foreach ($pageContents as $_) {
            $pageObjectIds[] = $nextObjectId++;
            $contentObjectIds[] = $nextObjectId++;
        }

        $fontObjectId = $nextObjectId++;
        $objects = [];
        $objects[$catalogObjectId] = sprintf(
            "%d 0 obj\n<< /Type /Catalog /Pages %d 0 R >>\nendobj\n",
            $catalogObjectId,
            $pagesObjectId
        );

        $kids = implode(' ', array_map(static fn (int $id): string => sprintf('%d 0 R', $id), $pageObjectIds));
        $objects[$pagesObjectId] = sprintf(
            "%d 0 obj\n<< /Type /Pages /Kids [%s] /Count %d >>\nendobj\n",
            $pagesObjectId,
            $kids,
            count($pageObjectIds)
        );

        foreach ($pageContents as $index => $content) {
            $pageObjectId = $pageObjectIds[$index];
            $contentObjectId = $contentObjectIds[$index];

            $objects[$pageObjectId] = sprintf(
                "%d 0 obj\n<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>\nendobj\n",
                $pageObjectId,
                $pagesObjectId,
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $fontObjectId,
                $contentObjectId
            );

            $objects[$contentObjectId] = sprintf(
                "%d 0 obj\n<< /Length %d >>\nstream\n%sendstream\nendobj\n",
                $contentObjectId,
                strlen($content),
                $content
            );
        }

        $objects[$fontObjectId] = sprintf(
            "%d 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            $fontObjectId
        );

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    private static function textCommand(float $x, float $y, float $fontSize, string $text): string
    {
        return sprintf(
            'BT /F1 %.1F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
            $fontSize,
            $x,
            $y,
            self::escapeText($text)
        );
    }

    private static function lineCommand(float $x1, float $y1, float $x2, float $y2): string
    {
        return sprintf('0.7 w %.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    private static function toPdfY(float $yTop): float
    {
        return self::PAGE_HEIGHT - $yTop;
    }

    private static function escapeText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }

    private static function truncate(string $text, int $maxLength): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
        if ($length <= $maxLength) {
            return $text;
        }

        $visible = max(1, $maxLength - 3);
        $truncated = function_exists('mb_substr')
            ? mb_substr($text, 0, $visible)
            : substr($text, 0, $visible);

        return rtrim($truncated) . '...';
    }
}
