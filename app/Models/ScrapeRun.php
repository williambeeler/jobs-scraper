<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $scrape_schedule_id
 * @property string $keywords
 * @property int $max_days
 * @property string[] $sources
 * @property string $status
 * @property int $jobs_found
 * @property int $jobs_created
 * @property int $jobs_updated
 * @property array<string, string>|null $errors
 * @property Carbon $started_at
 * @property Carbon|null $completed_at
 */
class ScrapeRun extends Model
{
    protected $fillable = [
        'scrape_schedule_id',
        'keywords',
        'max_days',
        'sources',
        'status',
        'jobs_found',
        'jobs_created',
        'jobs_updated',
        'errors',
        'started_at',
        'completed_at',
    ];

    /**
     * @return BelongsTo<ScrapeSchedule, $this>
     */
    public function scrapeSchedule(): BelongsTo
    {
        return $this->belongsTo(ScrapeSchedule::class);
    }

    /**
     * @return BelongsToMany<JobListing, $this>
     */
    public function jobListings(): BelongsToMany
    {
        return $this->belongsToMany(JobListing::class);
    }

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
