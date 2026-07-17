<?php

namespace App\Models;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'company_key',
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

    public function companyPreference(): HasOne
    {
        return $this->hasOne(CompanyPreference::class, 'company_key', 'company_key');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNotIn('company_key', CompanyPreference::query()
            ->select('company_key')->where('type', CompanyPreferenceType::Blacklist));
    }

    public static function companyKey(?string $company): ?string
    {
        if ($company === null || trim($company) === '') {
            return null;
        }

        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $company)));
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
