<?php

namespace App\Services\TimeTrials;

use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventLap;
use App\Models\TimeTrialLap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

    /**
     * The top of the classification for the events sidebar, which is on every page:
     * cached until the event gets another lap (the key follows its lap count and newest
     * lap), and at most a few minutes so the all-time records behind it catch up too.
     * Only plain values go in the cache (it unserializes no classes, config/cache.php),
     * the laps themselves are loaded again in one query.
     */
    public function top(TimeTrialEvent $event, int $limit = 10): Collection
    {
        $laps = $event->laps()->reorder()->selectRaw('count(*) as lap_count, max(id) as last_id')->first();
        $key = "tt-standings-top-v2:{$event->id}:{$limit}:{$laps?->lap_count}:{$laps?->last_id}";

        $rows = Cache::remember($key, now()->addMinutes(5), fn () => $this->for($event)->take($limit)
            ->map(fn (array $row) => ['lap_id' => $row['lap']->id] + array_diff_key($row, ['lap' => true]))
            ->values()->all());

        $lapsById = TimeTrialEventLap::with('car')->whereKey(array_column($rows, 'lap_id'))->get()->keyBy('id');

        return collect($rows)
            ->filter(fn (array $row) => $lapsById->has($row['lap_id']))
            ->map(fn (array $row) => ['position' => $row['position'], 'lap' => $lapsById[$row['lap_id']], 'gap_ms' => $row['gap_ms'], 'points' => $row['points']])
            ->values();
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
