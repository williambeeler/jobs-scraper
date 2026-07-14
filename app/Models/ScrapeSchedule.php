<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $keywords
 * @property string[] $sources
 * @property string $configuration_hash
 * @property int $max_days
 * @property int $interval_hours
 * @property bool $is_active
 * @property Carbon|null $last_ran_at
 * @property Carbon|null $next_run_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ScrapeSchedule extends Model
{
    protected $fillable = [
        'keywords',
        'sources',
        'configuration_hash',
        'max_days',
        'interval_hours',
        'is_active',
        'last_ran_at',
        'next_run_at',
    ];

    /**
     * @return HasMany<ScrapeRun, $this>
     */
    public function scrapeRuns(): HasMany
    {
        return $this->hasMany(ScrapeRun::class);
    }

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'is_active' => 'boolean',
            'last_ran_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }
}
