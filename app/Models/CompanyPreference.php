<?php

namespace App\Models;

use App\Enums\CompanyPreferenceType;
use Illuminate\Database\Eloquent\Model;

class CompanyPreference extends Model
{
    protected $fillable = ['company_name', 'company_key', 'type'];

    protected function casts(): array
    {
        return ['type' => CompanyPreferenceType::class];
    }
}
