<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Collection;

// Per-entry ballast/restrictor for a championship round's entrylist: success
// ballast (settings.balance.success_ballast_*), plus the championship's manual
// "Ballast & Restrictor Adjustments" (Penalties & Balance step) for a driver or
// team entrant. Per-car adjustments aren't per entry — cars are balanced through
// the BOP instead — so they're not applied here. ACC adds both on top of the BOP.
//
// Success ballast is recalculated from the championship's results every time
// (history()), never stored incrementally — editing or re-importing a round's
// results is picked up by that round and every round after it.
class EntryBalanceService
{
    // ACC's ballast range, the same -40..+40 our own bop.json uses — success
    // ballast may always go below 0, down to the minimum.
    public const MAX_BALLAST_KG = 40;

    public const MIN_BALLAST_KG = -40;

    public const MAX_RESTRICTOR = 20;

    public const MODE_NEXT_ROUND = 'next_round';

    public const MODE_CUMULATIVE = 'cumulative';

    // Why a driver's ballast came out the way it did after a round (history()).
    public const REASON_STARTING = 'starting';

    public const REASON_NORMAL = 'normal';

    public const REASON_MISSED = 'missed';

    public const REASON_UNDER_MIN_LAPS = 'under_min_laps';

    public const REASON_CAPPED = 'capped';

    public const REASON_FLOORED = 'floored';

    /** @var array<int, array> championship id => history() */
    private array $history = [];

    /**
     * [ballastKg, restrictor] for one entry — a solo driver ($teamName null) or a
     * team car (every driver in it; the car carries the heaviest driver's ballast).
     *
     * @param  Collection<int, User>  $drivers
     */
    public function forEntry(Race $race, Collection $drivers, ?string $teamName = null): array
    {
        $championship = $this->championship($race);
        if (! $championship) {
            return [0, 0];
        }

        $config = $this->config($championship);
        $ballast = $config
            ? (int) $drivers->map(fn (User $user) => $this->ballastInto($race, $user->id, $championship))->max()
            : 0;
        $restrictor = 0;

        $entrant = $teamName ?? $drivers->first()?->displayName();
        foreach ($championship->settings->balance->adjustments ?? [] as $adjustment) {
            $adjustment = (array) $adjustment;
            if (($adjustment['scope'] ?? null) === 'driver' && $entrant !== null && ($adjustment['target'] ?? null) === $entrant) {
                $ballast += (int) ($adjustment['ballast_kg'] ?? 0);
                $restrictor += (int) ($adjustment['restrictor_percent'] ?? 0);
            }
        }

        return [
            max(self::MIN_BALLAST_KG, min(self::MAX_BALLAST_KG, $ballast)),
            max(0, min(self::MAX_RESTRICTOR, $restrictor)),
        ];
    }

    /**
     * Success ballast each of this round's drivers carries into it — everyone
     * with a ballast history so far plus the round's registered drivers (a
     * mid-season joiner's starting ballast). Zero-ballast drivers are left out.
     *
     * @return array<int, int> user id => kg
     */
    public function successBallast(Race $race): array
    {
        $championship = $this->championship($race);
        if (! $championship || ! $this->config($championship) || ! $race->round_number) {
            return [];
        }

        $userIds = collect(array_keys($this->history($championship)))
            ->merge($race->registrations()->pluck('user_id'))
            ->unique();

        return $userIds
            ->mapWithKeys(fn ($userId) => [$userId => $this->ballastInto($race, $userId, $championship)])
            ->filter()
            ->all();
    }

    /**
     * The success ballast one driver carries into $race: where their history
     * stood after the last finished round before it, or — before their first
     * appearance — the starting ballast for this round (cumulative mode).
     */
    public function ballastInto(Race $race, int $userId, ?Championship $championship = null): int
    {
        $championship ??= $this->championship($race);
        $config = $championship ? $this->config($championship) : null;
        if (! $config || ! $race->round_number) {
            return 0;
        }

        $rows = collect($this->history($championship)[$userId] ?? [])
            ->where('round', '<', $race->round_number);

        return $rows->isNotEmpty()
            ? $rows->last()['after']
            : $this->startingBallast($config, $race->round_number);
    }

    /**
     * Every driver's ballast round by round, from the championship's finished
     * rounds in order: user id => list of [round, before, position, delta, after,
     * reason]. A driver's list starts at the first round they actually raced
     * (not their sign-up), after which a round they miss is kept as "missed".
     *
     * @return array<int, list<array{round: int, before: int, position: ?int, delta: int, after: int, reason: string}>>
     */
    public function history(Championship $championship): array
    {
        if (isset($this->history[$championship->id])) {
            return $this->history[$championship->id];
        }

        $config = $this->config($championship);
        if (! $config) {
            return $this->history[$championship->id] = [];
        }

        $rounds = $championship->rounds()
            ->where('status', 'finished')
            ->whereNotNull('round_number')
            ->with('raceResults')
            ->get();

        $history = [];
        foreach ($rounds as $round) {
            $bestPositions = $this->bestPositions($round, $config['min_laps'], (bool) $championship->is_multiclass);
            $appeared = $round->raceResults->where('dns', false)->pluck('user_id')->filter()->unique();

            foreach ($appeared->merge(array_keys($history))->unique() as $userId) {
                $history[$userId][] = $this->step(
                    $config,
                    $round->round_number,
                    isset($history[$userId]) ? end($history[$userId])['after'] : null,
                    $appeared->contains($userId),
                    $bestPositions[$userId] ?? null,
                );
            }
        }

        return $this->history[$championship->id] = $history;
    }

    /**
     * The history as chart lines for the championship page's Ballast Record: one
     * line per driver from the ballast they started with (round before their
     * first) to where they stand now, heaviest first. Null when there's nothing
     * to draw yet.
     *
     * @return array{rounds: int, series: list<array{user: User, points: list<array{0: int, 1: int}>, current: int}>}|null
     */
    public function chart(Championship $championship): ?array
    {
        $history = $this->history($championship);
        if (! $history) {
            return null;
        }

        $users = User::whereIn('id', array_keys($history))->get()->keyBy('id');

        $series = collect($history)
            ->filter(fn ($rows, $userId) => $users->has($userId))
            ->map(function (array $rows, int $userId) use ($users) {
                $points = [[$rows[0]['round'] - 1, $rows[0]['before']]];
                foreach ($rows as $row) {
                    $points[] = [$row['round'], $row['after']];
                }

                return ['user' => $users[$userId], 'points' => $points, 'current' => end($rows)['after']];
            })
            ->sortByDesc('current')
            ->values()
            ->all();

        return [
            'rounds' => (int) collect($history)->flatten(1)->max('round'),
            'series' => $series,
        ];
    }

    /** One driver's ballast change over one round. $before null = their first appearance. */
    private function step(array $config, int $round, ?int $before, bool $appeared, ?int $position): array
    {
        $first = $before === null;
        $before ??= $this->startingBallast($config, $round);

        if (! $appeared || $position === null) {
            return [
                'round' => $round, 'before' => $before, 'position' => null, 'delta' => 0, 'after' => $before,
                'reason' => ! $appeared ? self::REASON_MISSED : ($first ? self::REASON_STARTING : self::REASON_UNDER_MIN_LAPS),
            ];
        }

        $table = $config['table'];
        $delta = $table[min($position, count($table)) - 1];
        $raw = $config['mode'] === self::MODE_CUMULATIVE ? $before + $delta : $delta;
        $after = max(self::MIN_BALLAST_KG, min($config['cap'], $raw));

        return [
            'round' => $round, 'before' => $before, 'position' => $position, 'delta' => $delta, 'after' => $after,
            'reason' => match (true) {
                $after < $raw => self::REASON_CAPPED,
                $after > $raw => self::REASON_FLOORED,
                default => self::REASON_NORMAL,
            },
        ];
    }

    /**
     * Each driver's best finishing position (within their class when multiclass)
     * across a round's races, counting only races where they did at least
     * $minLaps laps. A team car's drivers all share the car's position.
     *
     * @return array<int, int> user id => position
     */
    private function bestPositions(Race $round, int $minLaps, bool $multiclass): array
    {
        $best = [];
        foreach ($round->raceResults->groupBy('race_number') as $results) {
            // One row per driver per car — group a team car's drivers back into one car.
            $cars = $results->groupBy(fn (RaceResult $result) => $result->car_number !== null ? 'car'.$result->car_number : 'user'.$result->user_id);

            $byClass = $cars->map(fn ($rows) => $rows->sortBy('position')->first())
                ->groupBy(fn (RaceResult $result) => $multiclass ? ($result->car_class ?? '') : '');

            foreach ($byClass as $classCars) {
                $positions = RaceResult::classifiedPositions($classCars);

                foreach ($classCars as $car) {
                    $position = $positions->get($car->id);
                    $carRows = $cars->get($car->car_number !== null ? 'car'.$car->car_number : 'user'.$car->user_id);
                    if ($position === null || $carRows->max('lap_count') < $minLaps) {
                        continue;
                    }
                    foreach ($carRows->pluck('user_id')->filter() as $userId) {
                        $best[$userId] = min($best[$userId] ?? PHP_INT_MAX, $position);
                    }
                }
            }
        }

        return $best;
    }

    private function startingBallast(array $config, int $round): int
    {
        if ($config['mode'] !== self::MODE_CUMULATIVE || ! $config['starting']) {
            return 0;
        }

        $starting = $config['starting'];

        return max(self::MIN_BALLAST_KG, min($config['cap'], $starting[min($round, count($starting)) - 1]));
    }

    /** The championship's success-ballast settings, or null when it's off. */
    private function config(Championship $championship): ?array
    {
        $balance = $championship->settings->balance;
        $table = self::parseTable($balance->success_ballast_kg ?? null);

        if (! ($balance->success_ballast_enabled ?? false) || ! $table) {
            return null;
        }

        $cap = max(0, min(self::MAX_BALLAST_KG, (int) ($balance->success_ballast_cap ?? self::MAX_BALLAST_KG)));

        return [
            'mode' => $balance->success_ballast_mode ?? self::MODE_NEXT_ROUND,
            'table' => $table,
            'cap' => $cap,
            'min_laps' => max(0, (int) ($balance->success_ballast_min_laps ?? 1)),
            'starting' => self::parseTable($balance->success_ballast_starting ?? null),
        ];
    }

    /** "5, 3, -1" => [5, 3, -1] — kg for P1, P2, P3… (or round 1, 2, 3…). */
    public static function parseTable(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_map(
            fn ($kg) => max(self::MIN_BALLAST_KG, min(self::MAX_BALLAST_KG, (int) trim($kg))),
            explode(',', $value)
        );
    }

    private function championship(Race $race): ?Championship
    {
        return $race->championship_id
            ? Championship::withoutTenantScope()->find($race->championship_id)
            : null;
    }
}
