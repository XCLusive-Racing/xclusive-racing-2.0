<?php

namespace App\Xp;

use DateTimeInterface;
use Illuminate\Support\Carbon;

// The moment racing XP starts (config xp.launch_at, UK time). A race from before it earns
// no racing XP; it does count towards achievements, like the rest of a driver's history
// (xp:award-history). With no launch date set, no race awards anything.
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
