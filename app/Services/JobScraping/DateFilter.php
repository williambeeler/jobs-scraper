<?php

namespace App\Services\JobScraping;

final class DateFilter
{
    public static function isWithinDays(?string $isoDate, int $maxDays): bool
    {
        if ($isoDate === null || $isoDate === '') {
            return true;
        }

        $posted = strtotime($isoDate);

        return $posted === false || $posted >= strtotime(sprintf('-%d days', $maxDays));
    }

    public static function isTimestampWithinDays(?int $timestampMs, int $maxDays): bool
    {
        if ($timestampMs === null) {
            return true;
        }

        return (int) floor($timestampMs / 1000) >= strtotime(sprintf('-%d days', $maxDays));
    }
}
