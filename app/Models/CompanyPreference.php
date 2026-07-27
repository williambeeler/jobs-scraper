<?php

namespace App\Models;

use App\Enums\CompanyPreferenceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPreference extends Model
{
    protected $fillable = ['company_id', 'type'];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return ['type' => CompanyPreferenceType::class];
    }
}
