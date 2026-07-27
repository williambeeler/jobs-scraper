<?php

namespace WilliamBeeler\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhoneCall extends CrmModel
{
    protected string $crmTable = 'phone_calls';

    use HasFactory;

    protected $fillable = [
        'company_id',
        'called_at',
        'notes',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
