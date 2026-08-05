<?php

namespace App\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Relationship extends CrmModel
{
    protected string $crmTable = 'relationships';

    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
