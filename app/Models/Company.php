<?php

namespace App\Models;

use App\Enums\CompanyPreferenceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string $name_key
 * @property-read CompanyPreference|null $preference
 *
 * @method static Builder<static> query()
 */
class Company extends Model
{
    protected $fillable = ['name', 'name_key'];

    public static function findOrCreateByName(?string $name): ?self
    {
        $nameKey = self::nameKey($name);

        if ($nameKey === null) {
            return null;
        }

        return self::query()->firstOrCreate(
            ['name_key' => $nameKey],
            ['name' => trim((string) preg_replace('/\s+/', ' ', $name))],
        );
    }

    public static function nameKey(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $name)));
    }

    /** @return HasMany<JobListing, $this> */
    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }

    /** @return HasOne<CompanyPreference, $this> */
    public function preference(): HasOne
    {
        return $this->hasOne(CompanyPreference::class);
    }

    public function hasPreference(CompanyPreferenceType $type): bool
    {
        if ($this->relationLoaded('preference')) {
            return $this->preference?->type === $type;
        }

        return $this->preference()->where('type', $type)->exists();
    }

    public function isWhitelisted(): bool
    {
        return $this->hasPreference(CompanyPreferenceType::Whitelist);
    }

    public function isVerified(): bool
    {
        return $this->hasPreference(CompanyPreferenceType::Verified);
    }

    public function isBlacklisted(): bool
    {
        return $this->hasPreference(CompanyPreferenceType::Blacklist);
    }
}
