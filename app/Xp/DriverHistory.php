<?php

namespace App\Xp;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\XpRaceFact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

// A driver's race history, loaded once and shaped for the metrics: one plain row per race
// session they have a result in since the launch (App\Xp\Launch), oldest first, each
// carrying its XP title and platform. Races from before the launch are not in it.
// rows($scope) is the one place a title scope is applied.
class DriverHistory
{
    /** @var array<int, self> */
    private static array $cache = [];

    private Collection $rows;

    private function __construct(public readonly User $user)
    {
        $this->rows = $this->load();
    }

    public static function for(User $user): self
    {
        return self::$cache[$user->id] ??= new self($user);
    }

    // After anything that changes a driver's results or profile, and between jobs.
    public static function forget(?int $userId = null): void
    {
        if ($userId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$userId]);
        }
    }

    /** Race rows in the given scope: 'all', or one title's. */
    public function rows(string $scope = 'all'): Collection
    {
        return $scope === 'all' ? $this->rows : $this->rows->where('game', $scope)->values();
    }

    private function load(): Collection
    {
        $launch = Launch::at();
        if (! $launch) {
            return collect();
        }

        $results = RaceResult::where('user_id', $this->user->id)
            ->where('session_type', 'race')
            ->where('race_scheduled_at', '>=', $launch)
            ->orderBy('race_scheduled_at')->orderBy('race_number')->orderBy('id')
            ->get();

        if ($results->isEmpty()) {
            return collect();
        }

        $raceIds = $results->pluck('race_id')->filter()->unique()->values();

        $races = Race::whereIn('id', $raceIds)
            ->with('eventFormat:id,name')
            ->get(['id', 'game', 'track', 'is_endurance', 'is_multiclass', 'championship_id', 'event_format_id', 'event_tag'])
            ->keyBy('id');

        // The driver's grid position per race (qualifying result), and the size of the grid.
        $grid = RaceResult::where('user_id', $this->user->id)->where('session_type', 'quali')
            ->whereIn('race_id', $raceIds)->get(['race_id', 'position'])
            ->groupBy('race_id')->map(fn ($rows) => (int) $rows->min('position'));
        $gridSize = RaceResult::where('session_type', 'quali')->whereIn('race_id', $raceIds)
            ->selectRaw('race_id, MAX(position) as size')->groupBy('race_id')->pluck('size', 'race_id');

        // Cars shared with another driver (a team entry), and the runner-up's time in races won.
        $others = RaceResult::where('session_type', 'race')->whereIn('race_id', $raceIds)
            ->where(fn ($q) => $q->where('user_id', '!=', $this->user->id)->orWhereNull('user_id'))
            ->get(['race_id', 'race_number', 'car_number', 'position', 'total_time', 'lap_count', 'player_id', 'dns']);
        // A race counts from two drivers on track: one where nobody else started is left out.
        $contested = $others->where('dns', false)->mapWithKeys(fn ($r) => [$r->race_id.'|'.$r->race_number => true]);
        $results = $results->filter(fn (RaceResult $r) => isset($contested[$r->race_id.'|'.$r->race_number]))->values();
        $sharedCars = $others->filter(fn ($r) => $r->car_number !== null)
            ->mapWithKeys(fn ($r) => [$r->race_id.'|'.$r->race_number.'|'.$r->car_number => true]);
        $runnerUp = $others->where('position', 2)
            ->mapWithKeys(fn ($r) => [$r->race_id.'|'.$r->race_number => [(int) $r->total_time, (int) $r->lap_count]]);

        $facts = XpRaceFact::whereIn('race_result_id', $results->pluck('id'))->get()->keyBy('race_result_id');

        return $results->map(function (RaceResult $r) use ($races, $grid, $gridSize, $sharedCars, $runnerUp, $facts) {
            $race = $r->race_id ? $races->get($r->race_id) : null;
            $raceGame = $r->race_game ?: $race?->game;
            $track = $r->race_track ?: $race?->track;
            $started = ! $r->dns;
            $completed = $started && ! $r->dnf && ! $r->dsq && ! $r->dc;
            $fact = $facts->get($r->id);
            $second = $runnerUp->get($r->race_id.'|'.$r->race_number);

            return (object) [
                'id' => $r->id,
                'race_id' => $r->race_id,
                'race_number' => (int) ($r->race_number ?? 1),
                'game' => Titles::game($raceGame),
                'platform' => Titles::platform($raceGame),
                'at' => $r->race_scheduled_at ? Carbon::parse($r->race_scheduled_at) : $r->created_at,
                'track' => Titles::trackKey($track),
                'nordschleife' => Titles::isNordschleife($track),
                'vehicle' => $r->vehicle,
                'car_class' => $r->car_class ? strtoupper($r->car_class) : null,
                'position' => (int) $r->position,
                'laps' => (int) $r->lap_count,
                'total_time' => (int) $r->total_time,
                'fastest_lap' => (bool) $r->fastest_lap,
                'started' => $started,
                'completed' => $completed,
                'grid' => $r->race_id ? $grid->get($r->race_id) : null,
                'grid_size' => $r->race_id ? (int) ($gridSize[$r->race_id] ?? 0) : 0,
                'endurance' => (bool) $race?->is_endurance,
                'multiclass' => (bool) $race?->is_multiclass,
                'championship' => (bool) $race?->championship_id,
                'format' => $race?->eventFormat?->name ?? $race?->event_tag,
                'shared_car' => $r->car_number !== null && isset($sharedCars[$r->race_id.'|'.$r->race_number.'|'.$r->car_number]),
                'gap_to_second_ms' => $second && $second[1] === (int) $r->lap_count && $second[0] > 0 && $r->total_time ? $second[0] - (int) $r->total_time : null,
                'rating_before' => $r->rating_before !== null ? (float) $r->rating_before : null,
                'rating_after' => $r->rating_after !== null ? (float) $r->rating_after : null,
                'sr_after' => $r->sr_after !== null ? (float) $r->sr_after : null,
                // No in-game penalty, no steward time penalty; null when the race's file isn't stored.
                'clean' => $fact?->penalty_free === null ? null : ($fact->penalty_free && (int) $r->time_penalty_ms === 0),
                'all_laps_valid' => $fact?->all_laps_valid,
            ];
        })->values();
    }
}
