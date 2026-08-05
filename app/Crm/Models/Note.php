<?php

namespace App\Crm\Models;

use App\Crm\Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends CrmModel
{
    protected string $crmTable = 'notes';

    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): NoteFactory
    {
        return NoteFactory::new();
    }

    protected $fillable = [
        'company_id',
        'content',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
