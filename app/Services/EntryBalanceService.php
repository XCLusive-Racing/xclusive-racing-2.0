<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Collection;

// Per-entry ballast/restrictor for a championship round's entrylist: success
// ballast earned in the previous round, plus the championship's manual
// "Ballast & Restrictor Adjustments" (Penalties & Balance step) for a driver or
// team entrant. Per-car adjustments aren't per entry — cars are balanced through
// the BOP instead — so they're not applied here.
class EntryBalanceService
{
    // ACC's own entrylist limits.
    public const MAX_BALLAST_KG = 100;

    public const MAX_RESTRICTOR = 20;

    /** @var array<int, array<int, int>> race id => [user id => kg] */
    private array $successBallast = [];

    /**
     * [ballastKg, restrictor] for one entry — a solo driver ($teamName null) or a
     * team car (every driver in it; the car carries the heaviest earned ballast).
     *
     * @param  Collection<int, User>  $drivers
     */
    public function forEntry(Race $race, Collection $drivers, ?string $teamName = null): array
    {
        $championship = $this->championship($race);
        if (! $championship) {
            return [0, 0];
        }

        $success = $this->successBallast($race);
        $ballast = (int) $drivers->map(fn (User $user) => $success[$user->id] ?? 0)->max();
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
            max(0, min(self::MAX_BALLAST_KG, $ballast)),
            max(0, min(self::MAX_RESTRICTOR, $restrictor)),
        ];
    }

    /**
     * Success ballast each driver carries into this round, from their finishing
     * position in the previous finished round (only the next round — it doesn't
     * build up). Multiclass ranks within each car class. A team car's drivers all
     * get the car's ballast.
     *
     * @return array<int, int> user id => kg
     */
    public function successBallast(Race $race): array
    {
        if (isset($this->successBallast[$race->id])) {
            return $this->successBallast[$race->id];
        }

        $championship = $this->championship($race);
        $table = $championship ? self::parseTable($championship->settings->balance->success_ballast_kg ?? null) : [];

        if (! $championship || ! ($championship->settings->balance->success_ballast_enabled ?? false) || ! $table || ! $race->round_number) {
            return $this->successBallast[$race->id] = [];
        }

        $previous = $championship->rounds()
            ->where('round_number', '<', $race->round_number)
            ->where('status', 'finished')
            ->reorder('round_number', 'desc')
            ->first();

        if (! $previous) {
            return $this->successBallast[$race->id] = [];
        }

        // One row per driver per car — group a team car's drivers back into one car,
        // then rank the cars (within their class when multiclass).
        $cars = $previous->raceResults()->where('dnf', false)->where('dns', false)->get()
            ->groupBy(fn ($result) => $result->car_number !== null ? 'car'.$result->car_number : 'user'.$result->user_id)
            ->map(fn ($rows) => [
                'position' => $rows->min('position'),
                'class' => $rows->first()->car_class,
                'user_ids' => $rows->pluck('user_id')->filter()->all(),
            ])
            ->sortBy('position')
            ->groupBy(fn ($car) => $championship->is_multiclass ? ($car['class'] ?? '') : '');

        $ballast = [];
        foreach ($cars as $classCars) {
            foreach ($classCars->values() as $index => $car) {
                $kg = $table[$index] ?? 0;
                foreach ($car['user_ids'] as $userId) {
                    if ($kg > 0) {
                        $ballast[$userId] = $kg;
                    }
                }
            }
        }

        return $this->successBallast[$race->id] = $ballast;
    }

    /** "30, 20, 10" => [30, 20, 10] — kg for P1, P2, P3… */
    public static function parseTable(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_map(fn ($kg) => min(self::MAX_BALLAST_KG, (int) trim($kg)), explode(',', $value));
    }

    private function championship(Race $race): ?Championship
    {
        return $race->championship_id
            ? Championship::withoutTenantScope()->find($race->championship_id)
            : null;
    }
}
