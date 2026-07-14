<?php

namespace App\Models;

use App\Enums\JobListingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source
 * @property string $title
 * @property string|null $company
 * @property string|null $location
 * @property string $url
 * @property string $url_hash
 * @property Carbon|null $posted_at
 * @property string|null $posted_relative
 * @property string|null $salary
 * @property JobListingStatus|null $status
 * @property Carbon|null $status_changed_at
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> query()
 */
class JobListing extends Model
{
    protected $fillable = [
        'source',
        'title',
        'company',
        'location',
        'url',
        'url_hash',
        'posted_at',
        'posted_relative',
        'salary',
        'status',
        'status_changed_at',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * @return BelongsToMany<ScrapeRun, $this>
     */
    public function scrapeRuns(): BelongsToMany
    {
        return $this->belongsToMany(ScrapeRun::class);
    }

    protected function casts(): array
    {
        return [
            'posted_at' => 'date',
            'status' => JobListingStatus::class,
            'status_changed_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
