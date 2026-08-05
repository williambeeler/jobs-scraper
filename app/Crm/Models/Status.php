<?php

namespace App\Crm\Models;

use App\Crm\Database\Factories\StatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends CrmModel
{
    protected string $crmTable = 'statuses';

    /** @use HasFactory<\Database\Factories\StatusFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): StatusFactory
    {
        return StatusFactory::new();
    }

    protected $fillable = [
        'name',
        'description',
        'slug',
        'color',
        'is_default',
    ];

    // A Status can belong to many Companies
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}
