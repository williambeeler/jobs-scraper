<?php

namespace App\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BusinessProfile extends CrmModel
{
    protected string $crmTable = 'business_profiles';

    use HasFactory;

    protected $fillable = [
        'business_name',
        'street_line_1',
        'street_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
