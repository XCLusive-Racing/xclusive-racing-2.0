<?php

namespace App\Services;

use App\Models\Race;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

// The dashboard's featured row: the same three picks as the top of the Events page
// (RaceController::featured()), but across every game at once —
// - popular: of the events starting in the next 24 hours, the one with the most real sign-ups;
// - weekly:  the next Weekly Event (Race::isWeeklyEvent());
// - monthly: the next Special Event (Race::isSpecialEvent()).
// The dashboard is on every page, so each pick is its own narrow query instead of the
// Events page's "load every upcoming race".
class DashboardFeaturedEvents
{
    private const COLUMNS = ['id', 'title', 'game', 'track', 'scheduled_at', 'status', 'max_drivers', 'image', 'icon', 'event_format_id', 'championship_id', 'is_championship', 'is_endurance'];

    /** @return array{popular: ?Race, weekly: ?Race, monthly: ?Race} */
    public function picks(): array
    {
        $weekly = $this->listed()
            ->whereIn('scheduled_at', $this->weeklySlots())
            ->whereNull('championship_id')->where('is_championship', false)
            ->get()->first(fn (Race $race) => $race->isWeeklyEvent());

        $monthly = $this->listed()->specialEvents()->first();

        $taken = array_filter([$weekly?->id, $monthly?->id]);
        $popular = $this->listed()
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDay())
            ->whereNotIn('id', $taken)
            ->get()
            ->filter(fn (Race $race) => $race->realSignupCount() > 0)
            ->sortByDesc(fn (Race $race) => $race->realSignupCount())
            ->first();

        return ['popular' => $popular, 'weekly' => $weekly, 'monthly' => $monthly];
    }

    // What the Events page lists: public, not finished, and not started more than
    // Race::LIVE_LISTING_MINUTES ago — in start-time order.
    private function listed(): Builder
    {
        return Race::select(self::COLUMNS)
            ->withIconOwners()
            ->withCount(['registrations', 'teamEntries'])
            ->where('status', '!=', 'finished')
            ->publiclyListed()
            ->where('scheduled_at', '>', now()->subMinutes(Race::LIVE_LISTING_MINUTES))
            ->orderBy('scheduled_at');
    }

    // The weekly event's start time (UK) for the coming two weeks, as stored (UTC).
    private function weeklySlots(): array
    {
        [$hour, $minute] = explode(':', Race::WEEKLY_EVENT_TIME);
        $first = now('Europe/London')->startOfDay();
        while ($first->dayOfWeek !== Race::WEEKLY_EVENT_DAY) {
            $first->addDay();
        }

        return array_map(
            fn (int $week) => Carbon::create($first->year, $first->month, $first->day, (int) $hour, (int) $minute, 0, 'Europe/London')
                ->addWeeks($week)->utc()->format('Y-m-d H:i:s'),
            [0, 1],
        );
    }
}
