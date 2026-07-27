<?php

namespace WilliamBeeler\Crm\Models;

use WilliamBeeler\Crm\Support\TableNames;

class Tag extends \Spatie\Tags\Tag
{
    public function getTable(): string
    {
        return TableNames::get('tags');
    }
}
