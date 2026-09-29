<?php

namespace App\Services\TimeTrials;

// Rating points for a weekly Time Trial event's final classification. Placeholder formula
// (to be replaced): the winner gets FIRST, the last driver LAST, linear in between. Never
// negative. The one place the formula lives, so swapping it is a change to points() only.
final class TimeTrialRating
{
    public const FIRST = 50;

    public const LAST = 1;

    public static function points(int $position, int $field): int
    {
        if ($field <= 1) {
            return self::FIRST;
        }

        return (int) round(self::FIRST - ($position - 1) * (self::FIRST - self::LAST) / ($field - 1));
    }
}
