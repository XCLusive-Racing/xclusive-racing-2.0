<?php

namespace App\Services;

use App\Models\Race;
use App\Models\RaceResult;

// Phase 6 (docs/championships/PLAN.md): "post-race time penalties applied to
// results" — no automatic position/time recalculation existed anywhere before
// this. Re-derives finishing order from scratch on every call rather than
// nudging positions around a single change, so it stays correct no matter how
// many penalties have stacked up on a session.
class ResultPenaltyService
{
    /**
     * Applies $penaltyMs to $result's time_penalty_ms (additive — a second penalty
     * stacks on top of the first, it doesn't replace it) and re-ranks every result
     * in the same race + session (and, for a multiclass race, the same car_class)
     * to reflect it. Championship::buildDriverStandings() reads RaceResult.position
     * live, so points/standings pick this up automatically — no separate
     * "recompute points" step needed. Rating is deliberately left untouched here,
     * same as the existing DSQ/DC status toggle
     * (Admin\RaceResultController::updateStatus()) — the admin/steward applies this,
     * then clicks "Recalculate Ratings" separately if the outcome should also move
     * ratings, rather than this silently triggering a race-wide Elo recompute.
     */
    public function applyPenalty(RaceResult $result, int $penaltyMs): void
    {
        $result->update(['time_penalty_ms' => $result->time_penalty_ms + $penaltyMs]);

        $this->recomputePositions($result->race, $result->session_type);
    }

    public function recomputePositions(Race $race, string $sessionType): void
    {
        $results = RaceResult::where('race_id', $race->id)
            ->where('session_type', $sessionType)
            ->get();

        $groups = $race->is_multiclass
            ? $results->groupBy(fn (RaceResult $r) => $r->car_class ?? 'Other')
            : collect(['__all__' => $results]);

        foreach ($groups as $group) {
            // DNS/DSQ are excluded from classification entirely — same rule
            // RaceResult::classifiedPositions() already applies for display.
            $classified = $group->where('dns', false)->where('dsq', false)
                ->sort(function (RaceResult $a, RaceResult $b) {
                    // Laps completed decide finishing order before time does — a driver
                    // who did more laps always ranks ahead, time only breaks a tie among
                    // drivers on the same lap (matches standard racing classification
                    // rules; ACC's own imported position already followed this before any
                    // penalty existed).
                    $lapDiff = ($b->lap_count ?? 0) <=> ($a->lap_count ?? 0);
                    if ($lapDiff !== 0) {
                        return $lapDiff;
                    }

                    return $this->effectiveTime($a) <=> $this->effectiveTime($b);
                })
                ->values();

            foreach ($classified as $i => $result) {
                $newPosition = $i + 1;
                if ($result->position !== $newPosition) {
                    $result->update(['position' => $newPosition]);
                }
            }
        }
    }

    private function effectiveTime(RaceResult $result): int|float
    {
        return $result->total_time !== null
            ? $result->total_time + $result->time_penalty_ms
            : PHP_INT_MAX;
    }
}
