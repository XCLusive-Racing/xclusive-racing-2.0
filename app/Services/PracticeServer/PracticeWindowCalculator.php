<?php

namespace App\Services\PracticeServer;

use App\Models\PracticeServer;
use App\Models\Race;
use Carbon\Carbon;

// Computes the practice window for an event against a practice server's fixed restart
// cadence. There is no fixed "two hours before" rule — the server restarts on its own
// schedule (every restart_cadence_minutes, starting at restart_offset_minutes past local
// midnight), and we work backwards from the event start to find the latest restart that
// still leaves at least sixty minutes of practice before the race.
//
// go-live (window_start) = latest restart boundary at or before (event start - 60 min)
// upload_at               = go-live minus five minutes (also the registration cutoff)
// window_end              = event start
//
// Was minus one minute — too tight against practice:push-due's own 5-minute polling
// interval (routes/console.php): a push whose upload_at landed just after a poll could
// sit for nearly 5 more minutes before the next one picked it up, arriving after the
// server had already reset instead of before it. Widened by 4 minutes so a same-length
// polling delay still lands at/before go-live rather than after it.
//
// All boundary arithmetic is done in Europe/London wall-clock time (matching the
// existing FtpServer::isValidSlot() convention elsewhere in this app), so restarts stay
// pinned to the same local clock time year-round regardless of DST.
class PracticeWindowCalculator
{
    private const MINIMUM_LEAD_MINUTES = 60;
    private const UPLOAD_LEAD_MINUTES  = 5;

    public function calculate(Race $race, PracticeServer $server): PracticeWindow
    {
        $localStart  = $race->scheduled_at->copy()->timezone('Europe/London');
        $cutoffLimit = $localStart->copy()->subMinutes(self::MINIMUM_LEAD_MINUTES);

        $goLive = $this->latestBoundaryAtOrBefore(
            $cutoffLimit,
            (int) $server->restart_cadence_minutes,
            (int) $server->restart_offset_minutes
        );

        $uploadAt = $goLive->copy()->subMinutes(self::UPLOAD_LEAD_MINUTES);

        return new PracticeWindow(
            windowStart: $goLive->utc(),
            uploadAt: $uploadAt->utc(),
            windowEnd: $localStart->copy()->utc(),
        );
    }

    private function latestBoundaryAtOrBefore(Carbon $target, int $cadence, int $offset): Carbon
    {
        $dayStart             = $target->copy()->startOfDay();
        $minutesSinceMidnight = (int) round($dayStart->diffInMinutes($target, false));

        if ($minutesSinceMidnight < $offset) {
            // Target falls before today's first boundary — the latest boundary is on
            // the previous (local) day.
            $prevDayStart  = $dayStart->copy()->subDay()->startOfDay();
            $prevDayLength = (int) round($prevDayStart->diffInMinutes($dayStart)); // handles DST-shortened/lengthened days

            $k = max(0, intdiv($prevDayLength - $offset, $cadence));

            return $prevDayStart->copy()->addMinutes($offset + $k * $cadence);
        }

        $k = intdiv($minutesSinceMidnight - $offset, $cadence);

        return $dayStart->copy()->addMinutes($offset + $k * $cadence);
    }
}
