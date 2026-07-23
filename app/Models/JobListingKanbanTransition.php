<?php

namespace App\Models;

use App\Enums\KanbanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobListingKanbanTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'from_status',
        'to_status',
        'transitioned_at',
    ];

    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    protected function casts(): array
    {
        return [
            'from_status' => KanbanStatus::class,
            'to_status' => KanbanStatus::class,
            'transitioned_at' => 'datetime',
        ];
    }
}
