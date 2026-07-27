<?php

namespace WilliamBeeler\Crm\Models;

use WilliamBeeler\Crm\Database\Factories\CompanyFactory;
use WilliamBeeler\Crm\Models\Note;
use WilliamBeeler\Crm\Models\PhoneCall;
use WilliamBeeler\Crm\Models\Relationship;
use WilliamBeeler\Crm\Models\Status;
use Spatie\Tags\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends CrmModel
{
    protected string $crmTable = 'companies';

    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, SoftDeletes, HasTags;

    protected $casts = [
        'meta' => 'array',
        'opportunity_score_index' => 'integer',
        'needs_callback' => 'boolean',
        'callback_at' => 'datetime',
    ];
    protected $guarded = [];

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected static function booted()
    {
        static::creating(function ($company) {
            //Set default status if none provided
            if (!$company->status_id) {
                $defaultStatus = Status::where('is_default', true)->first();
                if ($defaultStatus) {
                    $company->status_id = $defaultStatus->id;
                }
            }

            if (!$company->relationship_id) {
                $defaultRelationship = Relationship::where('slug', 'lead')->first();
                if ($defaultRelationship) {
                    $company->relationship_id = $defaultRelationship->id;
                }
            }
            //Ensure at least one category tag
            // if ($company->tagsWithType('category')->isEmpty()) {
            //     $uncategorized = Tag::findOrCreate('Uncategorized', 'category');
            //     $company->attachTag($uncategorized);
            // }
        });
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function phoneCalls()
    {
        return $this->hasMany(PhoneCall::class)->latest('called_at');
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }
    
}
