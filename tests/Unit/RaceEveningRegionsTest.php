<?php

namespace Tests\Unit;

use App\Models\Race;
use Carbon\Carbon;
use Tests\TestCase;

// User-directed 2026-09: each region of the Events page's Timezone filter covers
// 14:00-23:59 in its own local time, so the regions overlap.
class RaceEveningRegionsTest extends TestCase
{
    private function regionsAt(string $utc): array
    {
        $race = new Race;
        $race->scheduled_at = Carbon::parse($utc, 'UTC');

        return $race->eveningRegions();
    }

    public function test_each_region_runs_from_two_pm_to_midnight_local(): void
    {
        // 1 Oct 2026: London BST (UTC+1), New York EDT (UTC-4), Sydney AEST (UTC+10).
        $this->assertSame(['europe', 'australia'], $this->regionsAt('2026-10-01 13:00')); // 14:00 London, 23:00 Sydney
        $this->assertSame(['australia'], $this->regionsAt('2026-10-01 12:59')); // 13:59 London, 22:59 Sydney
        $this->assertSame(['europe'], $this->regionsAt('2026-10-01 14:00'));   // 15:00 London, 00:00 Sydney
        $this->assertSame(['europe', 'us'], $this->regionsAt('2026-10-01 20:00')); // 21:00 London, 16:00 NY
        $this->assertSame(['us'], $this->regionsAt('2026-10-01 23:00'));       // 00:00 London, 19:00 NY
        $this->assertSame(['australia'], $this->regionsAt('2026-10-01 04:00')); // 14:00 Sydney
    }
}
