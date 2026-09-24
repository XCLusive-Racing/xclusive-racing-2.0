<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class RaceResult extends Model
{
    protected $fillable = [
        'race_id', 'race_title', 'race_track', 'race_game', 'race_scheduled_at',
        'session_type', 'user_id',
        'player_id', 'driver_name', 'car_number', 'vehicle', 'car_class',
        'position', 'best_lap', 'lap_count', 'laps_led', 'total_time', 'time_penalty_ms', 'consistency',
        'fastest_lap', 'dnf', 'dns', 'dsq', 'dc',
        'rating_before', 'rating_after', 'elo_change', 'sof', 'sr_change',
    ];

    protected function casts(): array
    {
        return [
            'fastest_lap' => 'boolean',
            'dnf' => 'boolean',
            'dns' => 'boolean',
            'dsq' => 'boolean',
            'dc' => 'boolean',
            'rating_before' => 'decimal:4',
            'rating_after' => 'decimal:4',
            'elo_change' => 'decimal:4',
            'sof' => 'decimal:2',
            'sr_change' => 'decimal:4',
        ];
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->driver_name ?? 'Unknown';
    }

    /**
     * Collapses one result row per co-driver into one row per car for endurance/team
     * races (import creates a row per driver, but they share identical car-level stats).
     * Non-endurance races pass through unchanged. Preserves the input's existing order.
     *
     * @param  Collection<int, self>  $results
     * @return Collection<int, object{result: self, label: string, sub: ?string}>
     */
    public static function groupedByCar(Collection $results, Race $race): Collection
    {
        if (! $race->is_endurance) {
            return $results->map(fn (self $r) => (object) [
                'result' => $r,
                'label' => $r->displayName(),
                'sub' => null,
            ]);
        }

        // Primary lookup is via each driver's registration, not the imported car_number —
        // a driver's in-game car number can drift from what they registered under (wrong
        // livery slot, renumbered mid-event), which used to make team names appear to show
        // up "randomly". The registration's team_entry_id is authoritative regardless.
        $teamEntryIdByUserId = $race->registrations->whereNotNull('team_entry_id')->pluck('team_entry_id', 'user_id');
        $teamEntriesById = $race->teamEntries->keyBy('id');
        $teamByCarNumber = $race->teamEntries->keyBy('car_number');

        return $results->groupBy('car_number')->map(function ($group) use ($teamEntryIdByUserId, $teamEntriesById, $teamByCarNumber) {
            $primary = $group->first();
            $names = $group->map->displayName()->implode(' / ');

            $teamEntryId = $group->map(fn ($r) => $teamEntryIdByUserId->get($r->user_id))->filter()->first();
            $team = $teamEntryId ? $teamEntriesById->get($teamEntryId) : $teamByCarNumber->get($primary->car_number);

            return (object) [
                'result' => $primary,
                'label' => $team?->team?->name ?? $names,
                'sub' => $team ? $names : null,
            ];
        })->values();
    }

    /**
     * Renumbers classified results 1..N after excluding DNS/DSQ rows, so a DSQ near the
     * front doesn't leave the field stuck showing a gapped P2, P3... — everyone behind it
     * shifts up to fill the gap. Keyed by result id; DNS/DSQ rows are absent from the map.
     * DNF keeps its imported position since it still counts toward classification (see
     * User::raceStats()).
     *
     * @param  Collection<int, self>  $results
     * @return Collection<int, int>
     */
    public static function classifiedPositions(Collection $results): Collection
    {
        return $results->where('dns', false)->where('dsq', false)
            ->sortBy('position')
            ->values()
            ->mapWithKeys(fn (self $r, int $i) => [$r->id => $i + 1]);
    }

    /**
     * Splits groupedByCar() rows into one group per race class (own P1..N each) for a
     * multiclass race, or a single unlabeled group otherwise. A row whose car_class doesn't
     * match any of the race's configured classes (missing data, or a class removed after the
     * race ran) still surfaces under an "Other" group instead of silently disappearing. A
     * multiclass race also gets a leading "Overall" group across the whole grid, since a
     * class win and an outright win are both things people want to see.
     *
     * Rows are cloned per group (not mutated in place) — the same result appears in both the
     * "Overall" group and its own class group, each needing a different ->pos.
     *
     * @param  Collection<int, object{result: self, label: string, sub: ?string}>  $groupedRows
     * @return Collection<int, object{label: ?string, color: ?string, rows: Collection}>
     */
    public static function classGroups(Collection $groupedRows, Race $race): Collection
    {
        $applyPositions = function ($rows) {
            $positions = self::classifiedPositions($rows->pluck('result'));

            return $rows->map(function ($row) use ($positions) {
                $clone = clone $row;
                $clone->pos = $positions->get($row->result->id);

                return $clone;
            })->values();
        };

        if (! $race->is_multiclass || $race->raceClasses->isEmpty()) {
            return collect([(object) [
                'label' => null,
                'color' => null,
                'rows' => $applyPositions($groupedRows),
            ]]);
        }

        $overall = (object) [
            'label' => 'Overall',
            'color' => null,
            'rows' => $applyPositions($groupedRows),
        ];

        $classes = $race->raceClasses->sortBy('sort_order')->values();
        $groups = $classes->map(function (RaceClass $class) use ($groupedRows, $applyPositions) {
            $rows = $groupedRows->filter(
                fn ($row) => $row->result->car_class && $class->car_class
                    && strtoupper($row->result->car_class) === strtoupper($class->car_class)
            )->values();

            return (object) [
                'label' => $class->name,
                'color' => $class->color,
                'rows' => $applyPositions($rows),
            ];
        });

        $matchedIds = $groups->flatMap(fn ($g) => $g->rows->pluck('result.id'));
        $leftover = $groupedRows->reject(fn ($row) => $matchedIds->contains($row->result->id))->values();

        if ($leftover->isNotEmpty()) {
            $groups->push((object) [
                'label' => 'Other',
                'color' => null,
                'rows' => $applyPositions($leftover),
            ]);
        }

        return collect([$overall])->merge($groups)->filter(fn ($g) => $g->rows->isNotEmpty())->values();
    }

    public static function formatMs(?int $ms): string
    {
        if ($ms === null || $ms <= 0) {
            return '—';
        }
        $minutes = intdiv($ms, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $millis = $ms % 1000;

        return $minutes > 0
            ? sprintf('%d:%02d.%03d', $minutes, $seconds, $millis)
            : sprintf('%d.%03d', $seconds, $millis);
    }
}
