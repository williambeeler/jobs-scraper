<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class BibleReferenceParser
{
    /** @return array<int, array{book_id: int, book_slug: string, start_chapter: int, start_verse: ?int, end_chapter: int, end_verse: ?int, reference: string}> */
    public static function parse(string $reference): array
    {
        $reference = trim($reference);
        $special = [
            '1 & 2 Thessalonians' => ['1 Thessalonians', '2 Thessalonians'],
            'Colossians, Philemon' => ['Colossians', 'Philemon'],
            '2 Peter, Jude' => ['2 Peter', 'Jude'],
            '2, 3 John' => ['2 John', '3 John'],
        ];

        if (isset($special[$reference])) {
            return array_merge(...array_map(self::parse(...), $special[$reference]));
        }

        [$book, $remainder] = self::matchBook($reference);
        $result = [];
        if ($remainder === '') {
            return [self::passage($book, 1, null, $book['chapters'], null, $reference)];
        }

        $remainder = preg_replace('/([0-9])[a-z]\b/i', '$1', $remainder) ?? $remainder;
        $remainder = preg_replace('/\s+/', '', $remainder) ?? $remainder;
        if (str_contains($remainder, ':') || preg_match('/\d+\.\d+/', $remainder)) {
            $range = str_replace(':', '.', $remainder);
            if (preg_match('/^(\d+)-(\d+)\.(\d+)$/', $range, $match)) {
                return [self::passage($book, (int) $match[1], null, (int) $match[2], (int) $match[3], $reference)];
            }
            if (! preg_match('/^(\d+)\.(\d+)(?:-(?:(\d+)\.)?(\d+))?$/', $range, $match)) {
                throw new InvalidArgumentException("Unsupported verse reference: {$reference}");
            }
            $startChapter = (int) $match[1];
            $startVerse = (int) $match[2];
            $endChapter = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : $startChapter;
            $endVerse = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : $startVerse;

            return [self::passage($book, $startChapter, $startVerse, $endChapter, $endVerse, $reference)];
        }

        foreach (preg_split('/\s*,\s*/', $remainder) ?: [] as $chapters) {
            if (! preg_match('/^(\d+)(?:-(\d+))?$/', $chapters, $match)) {
                throw new InvalidArgumentException("Unsupported chapter reference: {$reference}");
            }
            $start = (int) $match[1];
            $end = isset($match[2]) ? (int) $match[2] : $start;
            if ($book['chapters'] === 1 && $end > 1) {
                $result[] = self::passage($book, 1, $start, 1, $end, $reference);

                continue;
            }
            $result[] = self::passage($book, $start, null, $end, null, $reference);
        }

        return $result;
    }

    /** @return array{0: array{slug: string, name: string, chapters: int, id: int}, 1: string} */
    private static function matchBook(string $reference): array
    {
        $aliases = self::aliases();
        uksort($aliases, fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($aliases as $alias => $slug) {
            if (preg_match('/^'.preg_quote($alias, '/').'(?:\.\s*|\b\s*)/i', $reference, $match)) {
                $book = BibleBooks::find($slug);
                if ($book !== null) {
                    return [$book, trim(substr($reference, strlen($match[0])))];
                }
            }
        }

        throw new InvalidArgumentException("Unknown Bible book in reference: {$reference}");
    }

    /** @param array{slug: string, name: string, chapters: int, id: int} $book */
    private static function passage(array $book, int $startChapter, ?int $startVerse, int $endChapter, ?int $endVerse, string $reference): array
    {
        if ($startChapter < 1 || $endChapter > $book['chapters'] || $endChapter < $startChapter) {
            throw new InvalidArgumentException("Invalid chapter range: {$reference}");
        }

        return ['book_id' => $book['id'], 'book_slug' => $book['slug'], 'start_chapter' => $startChapter, 'start_verse' => $startVerse, 'end_chapter' => $endChapter, 'end_verse' => $endVerse, 'reference' => $reference];
    }

    /** @return array<string, string> */
    private static function aliases(): array
    {
        $aliases = [];
        foreach (BibleBooks::all() as $book) {
            $aliases[$book['name']] = $book['slug'];
        }

        return $aliases + [
            'Psalm' => 'psalms', 'Ps' => 'psalms', 'P' => 'psalms', 'Prov' => 'proverbs', 'Prv' => 'proverbs', 'Pr' => 'proverbs',
            'Gn' => 'genesis', 'Gen' => 'genesis', 'Exod' => 'exodus', 'Ex' => 'exodus', 'Lev' => 'leviticus', 'Num' => 'numbers', 'Deut' => 'deuteronomy', 'DEUT' => 'deuteronomy',
            'Dt' => 'deuteronomy', 'Josh' => 'joshua', 'Judg' => 'judges', '1 Sam' => '1-samuel', '1 Sa' => '1-samuel', '2 Sam' => '2-samuel', '1 Kgs' => '1-kings', '1 K' => '1-kings', '2 Kgs' => '2-kings', '2 Kg' => '2-kings',
            '1 Chr' => '1-chronicles', '1 CHRON' => '1-chronicles', '2 Chr' => '2-chronicles', '2 CHRON' => '2-chronicles', 'Neh' => 'nehemiah',
            'Eccl' => 'ecclesiastes', 'ECCLES' => 'ecclesiastes', 'Song of Songs' => 'song-of-solomon', 'SONGS' => 'song-of-solomon', 'Song' => 'song-of-solomon', 'Isa' => 'isaiah', 'Jer' => 'jeremiah', 'Lam' => 'lamentations', 'Ezek' => 'ezekiel', 'Ez' => 'ezekiel', 'Dan' => 'daniel', 'Esth' => 'esther',
            'Hos' => 'hosea', 'Mic' => 'micah', 'Obad' => 'obadiah', 'Hab' => 'habakkuk', 'Zeph' => 'zephaniah', 'Zech' => 'zechariah', 'Mal' => 'malachi',
            'Matt' => 'matthew', 'Mk' => 'mark', 'Lk' => 'luke', 'Jn' => 'john', 'Rom' => 'romans', '1 Cor' => '1-corinthians', '1 COR' => '1-corinthians', '2 Cor' => '2-corinthians', '2 COR' => '2-corinthians',
            'Gal' => 'galatians', 'Eph' => 'ephesians', 'Phil' => 'philippians', 'Col' => 'colossians', '1 Thess' => '1-thessalonians', '2 Thess' => '2-thessalonians',
            '1 Tim' => '1-timothy', '2 Tim' => '2-timothy', 'Phlm' => 'philemon', 'Heb' => 'hebrews', 'Jas' => 'james', '1 Pet' => '1-peter', '2 Pet' => '2-peter', '1 Jn' => '1-john', 'Rev' => 'revelation',
        ];
    }
}
