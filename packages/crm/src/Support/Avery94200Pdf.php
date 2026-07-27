<?php

namespace WilliamBeeler\Crm\Support;

class Avery94200Pdf
{
    private const PAGE_WIDTH = 612.0; // 8.5in * 72
    private const PAGE_HEIGHT = 792.0; // 11in * 72

    private const LEFT_MARGIN = 13.5; // 0.1875in * 72
    private const TOP_MARGIN = 36.0; // 0.5in * 72
    private const LABEL_WIDTH = 189.0; // 2.625in * 72
    private const LABEL_HEIGHT = 72.0; // 1in * 72
    private const H_GAP = 9.0; // 0.125in * 72
    private const LABELS_PER_ROW = 3;
    private const LABEL_ROWS_PER_PAGE = 10;
    private const LABELS_PER_PAGE = self::LABELS_PER_ROW * self::LABEL_ROWS_PER_PAGE;

    public static function generate(string $line1, string $line2, string $line3): string
    {
        $labels = array_fill(0, self::LABELS_PER_PAGE, [
            'line1' => $line1,
            'line2' => $line2,
            'line3' => $line3,
        ]);

        return self::generateLabels($labels);
    }

    /**
     * @param array<int, array{line1?: string, line2?: string, line3?: string}> $labels
     */
    public static function generateLabels(array $labels): string
    {
        $pages = array_chunk(array_values($labels), self::LABELS_PER_PAGE);
        if (empty($pages)) {
            $pages = [[]];
        }

        $catalogObjectId = 1;
        $pagesObjectId = 2;
        $nextObjectId = 3;
        $pageObjectIds = [];
        $contentObjectIds = [];

        foreach ($pages as $_page) {
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

        foreach ($pages as $index => $pageLabels) {
            $pageObjectId = $pageObjectIds[$index];
            $contentObjectId = $contentObjectIds[$index];
            $content = self::buildContentStreamForPage($pageLabels);

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

        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
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

    /**
     * @param array<int, array{line1?: string, line2?: string, line3?: string}> $labels
     */
    private static function buildContentStreamForPage(array $labels): string
    {
        $commands = [];
        $labelIndex = 0;

        for ($row = 0; $row < self::LABEL_ROWS_PER_PAGE; $row++) {
            for ($col = 0; $col < self::LABELS_PER_ROW; $col++) {
                $label = $labels[$labelIndex] ?? null;
                $labelIndex++;
                if ($label === null) {
                    continue;
                }

                $x = self::LEFT_MARGIN + ($col * (self::LABEL_WIDTH + self::H_GAP));
                $yTop = self::TOP_MARGIN + ($row * self::LABEL_HEIGHT);

                $textX = $x + 8;
                $textYTop = $yTop + 18;
                $lineHeight = 12;
                $maxLineWidth = self::LABEL_WIDTH - 16;
                $line1 = trim((string) ($label['line1'] ?? ''));
                $line2 = trim((string) ($label['line2'] ?? ''));
                $line3 = trim((string) ($label['line3'] ?? ''));
                $line1 = self::truncateForWidth($line1, 11.5, $maxLineWidth);
                $line2 = self::truncateForWidth($line2, 10.5, $maxLineWidth);
                $line3 = self::truncateForWidth($line3, 10.5, $maxLineWidth);

                $commands[] = self::textCommand($textX, self::toPdfY($textYTop), 11.5, $line1);
                $commands[] = self::textCommand($textX, self::toPdfY($textYTop + $lineHeight), 10.5, $line2);
                $commands[] = self::textCommand($textX, self::toPdfY($textYTop + ($lineHeight * 2)), 10.5, $line3);
            }
        }

        return implode("\n", $commands) . "\n";
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

    private static function truncateForWidth(string $text, float $fontSize, float $maxWidth): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Helvetica average glyph width is roughly 0.53em.
        $estimatedCharWidth = $fontSize * 0.53;
        $maxChars = max(1, (int) floor($maxWidth / $estimatedCharWidth));
        $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);

        if ($length <= $maxChars) {
            return $text;
        }

        $visibleChars = max(1, $maxChars - 3);
        $truncated = function_exists('mb_substr')
            ? mb_substr($text, 0, $visibleChars)
            : substr($text, 0, $visibleChars);

        return rtrim($truncated) . '...';
    }
}
