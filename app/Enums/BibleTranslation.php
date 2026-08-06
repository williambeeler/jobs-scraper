<?php

declare(strict_types=1);

namespace App\Enums;

enum BibleTranslation: string
{
    case CSB = 'csb';
    case ESV = 'esv';
    case NET = 'net';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function table(): string
    {
        return 'bible_translation_'.$this->value;
    }

    public static function default(): self
    {
        return self::NET;
    }
}
