<?php

namespace WilliamBeeler\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavedSearch extends CrmModel
{
    protected string $crmTable = 'saved_searches';

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array',
    ];
}
