<?php

namespace WilliamBeeler\Crm\Models;

use Illuminate\Database\Eloquent\Model;

class GooglePlacesUsageLog extends CrmModel
{
    protected string $crmTable = 'google_places_usage_logs';

    public const TierEssentials = 'essentials';
    public const TierPro = 'pro';
    public const TierEnterprise = 'enterprise';

    public const PointsByTier = [
        self::TierEssentials => 1,
        self::TierPro => 2,
        self::TierEnterprise => 10,
    ];

    protected $fillable = [
        'usage_date',
        'tier',
        'google_request_count',
        'points_used',
        'search_terms_count',
        'radius_miles',
        'max_companies',
        'imported_companies_count',
        'meta',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'meta' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public static function supportedTiers(): array
    {
        return [
            self::TierEssentials,
            self::TierPro,
            self::TierEnterprise,
        ];
    }

    public static function normalizeTier(?string $tier): string
    {
        $normalizedTier = strtolower(trim((string) $tier));

        if (! in_array($normalizedTier, self::supportedTiers(), true)) {
            return self::TierEssentials;
        }

        return $normalizedTier;
    }

    public static function pointsForTier(string $tier): int
    {
        return self::PointsByTier[self::normalizeTier($tier)] ?? self::PointsByTier[self::TierEssentials];
    }

    public static function displayLabel(string $tier): string
    {
        return match (self::normalizeTier($tier)) {
            self::TierEssentials => 'Essentials',
            self::TierPro => 'Pro',
            self::TierEnterprise => 'Enterprise',
            default => 'Essentials',
        };
    }
}
