<?php

namespace App\Xp;

use DateTimeInterface;
use Illuminate\Support\Carbon;

// The moment XP starts (config xp.launch_at, UK time). Everybody starts from zero: a race,
// a report or a time trial from before it earns nothing and counts towards nothing. With
// no launch date set, nothing counts at all.
class Launch
{
    public static function at(): ?Carbon
    {
        $value = config('xp.launch_at');

        return $value ? Carbon::parse($value, 'Europe/London')->utc() : null;
    }

    public static function counts(?DateTimeInterface $when): bool
    {
        $launch = self::at();

        return $launch !== null && $when !== null && $launch->lessThanOrEqualTo($when);
    }

    // For queries: the launch, or now when there is none, so that nothing matches.
    public static function since(): Carbon
    {
        return self::at() ?? now();
    }
}
