<?php

namespace App\Services;

use App\Jobs\SyncDiscordRankRole;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function __construct(private XclRating $calculator) {}

    /**
     * Calculate and persist ratings for all linked users in a race session.
     * Skips results with no linked user_id. For a multiclass race, each class is scored as
     * its own self-contained field (own SoF, own finish-position scale, own MIN_DRIVERS gate)
     * instead of one mixed grid — a GT4 class win shouldn't be scored as "6th out of 16"
     * just because five GT3 cars finished ahead. A class that doesn't reach MIN_DRIVERS
     * finishers is skipped on its own, same as a whole small race is skipped today; the rest
     * of the race's classes still get rated.
     */
    public function processRace(Race $race): void
    {
        $results = RaceResult::where('race_id', $race->id)
            ->where('session_type', 'race')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        if ($results->isEmpty()) {
            return;
        }

        $ratingField = $this->ratingField($race->game);

        if (! $ratingField) {
            return;
        }

        // Priority: format multiplier > custom xcl_r_multiplier > legacy duration_key > 1.0 default
        $this->calculator->MULTIPLIER = $race->eventFormat?->xcl_r_multiplier
            ?? $race->xcl_r_multiplier
            ?? ($race->duration_key ? ($this->calculator->DURATION_MULTIPLIERS[$race->duration_key] ?? 1.0) : 1.0);

        $groups = $race->is_multiclass
            ? $results->groupBy(fn (RaceResult $r) => $r->car_class ?? 'Other')
            : collect(['__all__' => $results]);

        // Co-drivers sharing one car (Phase 5a) produce one RaceResult row each with
        // identical car-level stats — without a shared key, XclRating would count that one
        // car as multiple field entries, inflating SoF/field-size for the whole race. Same
        // lookup precedence as RaceResult::groupedByCar(): the registration's team_entry_id
        // is authoritative (car numbers can drift mid-event), falling back to car_number for
        // any result with no linked registration.
        $teamEntryIdByUserId = RaceRegistration::where('race_id', $race->id)
            ->whereNotNull('team_entry_id')
            ->pluck('team_entry_id', 'user_id');

        $calculated = collect();

        foreach ($groups as $classKey => $classResults) {
            // Class-relative finishing order (P1..N within this class only), not the raw
            // grid position — reuses the same ranking the public results page shows per class.
            $positions = RaceResult::classifiedPositions($classResults);

            $entries = $classResults->map(function (RaceResult $r) use ($ratingField, $positions, $teamEntryIdByUserId) {
                // Undo this result's own previously-applied elo_change (if any) so recalculating
                // after a manual DSQ/DC correction re-baselines from the pre-this-race rating
                // instead of stacking a second delta on top of the first.
                $rating = (float) ($r->user->{$ratingField} ?? 1500) - (float) ($r->elo_change ?? 0);

                // The dnf flag itself (set on import from the 70%-of-leader-laps heuristic)
                // drives the DNF badge/status and freezes Safety Rating either way — but the
                // flat DNF rating penalty is reserved for an actual lap-0/1 retirement. Anyone
                // dnf-flagged who completed 2+ laps still just keeps their real ACC finishing
                // position and goes through the normal position-based formula.
                $isTrueDnf = $r->dnf && (int) ($r->lap_count ?? 0) <= 1;
                $status    = $r->dsq ? 'DSQ' : ($r->dns ? 'DNS' : ($r->dc ? 'DC' : ($isTrueDnf ? 'DNF' : 'FIN')));

                return [
                    'driver_id'  => $r->user_id,
                    'name'       => $r->displayName(),
                    'rating'     => $rating,
                    'finish_pos' => ($status === 'FIN') ? $positions->get($r->id) : null,
                    'status'     => $status,
                    'field_key'  => $teamEntryIdByUserId->get($r->user_id) ?? $r->car_number ?? ('solo_' . $r->id),
                ];
            })->values()->all();

            $finisherCount = collect($entries)->where('status', 'FIN')->count();
            \Log::info('RatingService: starting calculation', [
                'race_id'        => $race->id,
                'class'          => $classKey,
                'linked_drivers' => count($entries),
                'finishers'      => $finisherCount,
                'min_required'   => $this->calculator->MIN_DRIVERS,
            ]);

            try {
                $classCalculated = $this->calculator->processRace(
                    ['name' => $race->title, 'race_date' => $race->scheduled_at->toDateString()],
                    $entries
                );
            } catch (\InvalidArgumentException $e) {
                \Log::warning('RatingService: skipped — ' . $e->getMessage(), ['race_id' => $race->id, 'class' => $classKey]);
                continue;
            }

            $calculated = $calculated->merge($classCalculated);
        }

        if ($calculated->isEmpty()) {
            return;
        }

        $byUserId = $calculated->keyBy('driver_id');
        $srField  = $this->srField($race->game);

        DB::transaction(function () use ($results, $byUserId, $ratingField, $srField) {
            foreach ($results as $result) {
                $calc = $byUserId->get($result->user_id);

                if (! $calc) {
                    continue;
                }

                $result->update([
                    'rating_before' => $calc['rating_before'],
                    'rating_after'  => $calc['rating_after'],
                    'elo_change'    => $calc['elo_change'],
                    'sof'           => $calc['sof'],
                ]);

                User::where('id', $result->user_id)
                    ->update([$ratingField => $calc['rating_after']]);

                if ($srField) {
                    $this->applySrChange($result, $srField);
                }
            }
        });

        // Spaced out like the bulk sweep (SyncDiscordRanksCommand) — dispatching one right
        // after another for every finisher hits Discord's per-route rate limit, and a
        // dropped update here just gets logged, never retried.
        $byUserId->keys()->each(function ($userId) {
            SyncDiscordRankRole::dispatch($userId);
            usleep(300_000);
        });
    }

    /**
     * A single, auditable choke point for rating/SR changes that aren't a full race
     * recalculation — today, a steward-processed report's penalty/return (Phase 6,
     * docs/championships/PLAN.md). Behaviourally identical to what
     * Admin\ReportController::process() did inline before this: floor rating/SR at
     * zero, round the same way, update only the fields actually passed. Kept as
     * plain column writes (not routed through XclRating's race-wide Elo exchange,
     * which processRace() already handles for actual results) — this exists so
     * every non-race-result rating mutation lives in one place, not to change the
     * math.
     */
    public function applyManualAdjustment(User $user, string $eloField, float $eloDelta, ?string $srField = null, float $srDelta = 0.0): void
    {
        $updates = [$eloField => (int) round(max(0, (float) $user->{$eloField} + $eloDelta))];

        if ($srField) {
            $updates[$srField] = round(max(0, (float) $user->{$srField} + $srDelta), 2);
        }

        $user->update($updates);
    }

    private function ratingField(string $game): ?string
    {
        return User::eloColumn($game);
    }

    private function srField(string $game): ?string
    {
        return User::srColumn($game);
    }

    /**
     * Only a clean finish (not DSQ/DNS/DC/DNF) raises SR — new_sr = old_sr + (2.2 / old_sr)
     * * race multiplier, so the gain shrinks the closer a driver gets to the 9.99 cap. Undoes
     * this result's own previously-applied sr_change first (mirrors the elo re-baselining
     * above) so reprocessing after a correction doesn't stack.
     */
    private function applySrChange(RaceResult $result, string $srField): void
    {
        $current  = (float) ($result->user->{$srField} ?? 4.00);
        $baseline = min(max($current - (float) ($result->sr_change ?? 0), 0.00), 9.99);

        $isFinisher = ! $result->dsq && ! $result->dns && ! $result->dc && ! $result->dnf;

        if ($isFinisher) {
            $gain  = 2.2 / max($baseline, 0.01) * $this->calculator->MULTIPLIER;
            $newSr = min(max($baseline + $gain, 0.00), 9.99);
        } else {
            $newSr = $baseline;
        }

        $change = round($newSr - $baseline, 4);

        $result->update(['sr_change' => $change]);
        User::where('id', $result->user_id)->update([$srField => round($newSr, 2)]);
    }
}