<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExcludedTerm extends Model
{
    protected $fillable = ['term', 'term_key'];

    public static function key(string $term): string
    {
        return mb_strtolower(trim($term));
    }

    public static function matches(string $title): bool
    {
        return static::query()
            ->whereRaw('instr(?, term_key) > 0', [mb_strtolower($title)])
            ->exists();
    }
}
