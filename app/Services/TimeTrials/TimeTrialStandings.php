<?php

namespace App\Services\TimeTrials;

use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventLap;
use App\Models\TimeTrialLap;
use Illuminate\Support\Collection;

// A weekly event's classification: each driver's fastest counting lap, fastest first.
//
// A lap only counts when it beats that driver's own all-time record in the same car on
// this track (the All Time Records, not counting laps this event itself added). A driver
// who can't beat their record in one car has to pick another car to improve.
class TimeTrialStandings
{
    /** @return Collection<int, array{position: int, lap: TimeTrialEventLap, gap_ms: int, points: int}> */
    public function for(TimeTrialEvent $event): Collection
    {
        $laps = $event->laps()->with('car')->orderBy('lap_time_ms')->orderBy('recorded_at')->orderBy('id')->get();
        $records = $this->allTimeBests($event, $laps->pluck('platform_identifier')->unique()->all());

        $best = $laps
            ->filter(fn (TimeTrialEventLap $lap) => $this->counts($event, $lap, $records))
            ->unique('platform_identifier')
            ->values();

        $leader = $best->first()?->lap_time_ms;

        return $best->map(fn (TimeTrialEventLap $lap, int $i) => [
            'position' => $i + 1,
            'lap' => $lap,
            'gap_ms' => $lap->lap_time_ms - $leader,
            'points' => TimeTrialRating::points($i + 1, $best->count()),
        ]);
    }

    public function counts(TimeTrialEvent $event, TimeTrialEventLap $lap, array $records): bool
    {
        if ($event->car_class && $lap->car_class !== $event->car_class) {
            return false;
        }

        $record = $records[$lap->platform_identifier.'|'.$lap->car_id] ?? null;

        return $record === null || $lap->lap_time_ms < $record;
    }

    /**
     * "identifier|car_id" => the driver's all-time best lap (ms) in that car on the event's
     * track, ignoring laps this event merged in itself.
     *
     * @param  string[]  $identifiers
     */
    public function allTimeBests(TimeTrialEvent $event, array $identifiers): array
    {
        if (! $identifiers) {
            return [];
        }

        return TimeTrialLap::where('track', $event->track)
            ->whereIn('platform_identifier', $identifiers)
            ->where(fn ($q) => $q->whereNull('time_trial_event_id')->orWhere('time_trial_event_id', '!=', $event->id))
            ->groupBy('platform_identifier', 'car_id')
            ->selectRaw('platform_identifier, car_id, min(lap_time_ms) as best_ms')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->platform_identifier.'|'.$row->car_id => (int) $row->best_ms])
            ->all();
    }
}
