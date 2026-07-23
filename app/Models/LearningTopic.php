<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningTopic extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
    ];

    public const PRIORITIES = [
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];
}
