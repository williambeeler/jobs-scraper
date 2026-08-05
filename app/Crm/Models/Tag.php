<?php

namespace App\Crm\Models;

use App\Crm\Support\TableNames;

class Tag extends \Spatie\Tags\Tag
{
    public function getTable(): string
    {
        return TableNames::get('tags');
    }
}
