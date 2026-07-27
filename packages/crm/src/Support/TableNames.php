<?php

namespace WilliamBeeler\Crm\Support;

use InvalidArgumentException;

final class TableNames
{
    public static function get(string $table): string
    {
        $name = config("crm.tables.{$table}");

        if (! is_string($name) || $name === '') {
            throw new InvalidArgumentException("CRM table [{$table}] is not configured.");
        }

        return $name;
    }
}
