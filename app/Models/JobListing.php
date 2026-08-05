<?php

namespace App\Models;

use App\Enums\CompanyPreferenceType;
use App\Enums\JobListingOrigin;
use App\Enums\JobListingStatus;
use App\Enums\KanbanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $source
 * @property JobListingOrigin $listing_origin
 * @property string $title
 * @property int|null $company_id
 * @property-read Company|null $company
 * @property string|null $location
 * @property string $url
 * @property string $url_hash
 * @property Carbon|null $posted_at
 * @property-read string|null $posted_age
 * @property-read string|null $company_search_url
 * @property string|null $salary
 * @property JobListingStatus|null $status
 * @property Carbon|null $status_changed_at
 * @property KanbanStatus $kanban_status
 * @property Carbon|null $kanban_status_changed_at
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
        'listing_origin',
        'title',
        'company_id',
        'location',
        'url',
        'url_hash',
        'posted_at',
        'salary',
        'status',
        'status_changed_at',
        'kanban_status',
        'kanban_status_changed_at',
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

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyPreference(): HasOne
    {
        return $this->hasOne(CompanyPreference::class, 'company_id', 'company_id');
    }

    public function kanbanTransitions(): HasMany
    {
        return $this->hasMany(JobListingKanbanTransition::class);
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query->whereNull('company_id')->orWhereNotIn('company_id', CompanyPreference::query()
                    ->select('company_id')->where('type', CompanyPreferenceType::Blacklist));
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('excluded_terms')
                    ->whereRaw('instr(lower(job_listings.title), excluded_terms.term_key) > 0');
            });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function postedAge(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->posted_at === null) {
                return null;
            }

            $cacheKey = "job-listing:{$this->id}:posted-age:{$this->posted_at->toDateString()}";

            return Cache::remember(
                $cacheKey,
                now()->addHours(3),
                fn (): string => $this->posted_at->diffForHumans(),
            );
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function companySearchUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $companyName = $this->company?->name;

            if ($companyName === null || trim($companyName) === '') {
                return null;
            }

            $query = trim($companyName.' '.($this->location ?? ''));

            return 'https://www.google.com/search?q='.urlencode($query);
        });
    }

    protected function casts(): array
    {
        return [
            'listing_origin' => JobListingOrigin::class,
            'posted_at' => 'date',
            'status' => JobListingStatus::class,
            'status_changed_at' => 'datetime',
            'kanban_status' => KanbanStatus::class,
            'kanban_status_changed_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
