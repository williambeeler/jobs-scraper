<?php

namespace WilliamBeeler\Crm\Models;

use WilliamBeeler\Crm\Database\Factories\NoteFactory;
use WilliamBeeler\Crm\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
