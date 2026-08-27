<?php

namespace App\Services;

use App\Jobs\SyncDiscordRankRole;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function __construct(private XclRating $calculator) {}

    /**
     * Calculate and persist ratings for all linked users in a race session.
     * Skips results with no linked user_id.
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

        $entries = $results->map(function (RaceResult $r) use ($ratingField) {
            // Undo this result's own previously-applied elo_change (if any) so recalculating
            // after a manual DSQ/DC correction re-baselines from the pre-this-race rating
            // instead of stacking a second delta on top of the first.
            $rating = (float) ($r->user->{$ratingField} ?? 1500) - (float) ($r->elo_change ?? 0);
            $status = $r->dsq ? 'DSQ' : ($r->dns ? 'DNS' : ($r->dc ? 'DC' : ($r->dnf ? 'DNF' : 'FIN')));

            return [
                'driver_id'  => $r->user_id,
                'name'       => $r->displayName(),
                'rating'     => $rating,
                'finish_pos' => ($status === 'FIN') ? $r->position : null,
                'status'     => $status,
            ];
        })->values()->all();

        // dnf is only ever true for a lap-0/1 retirement (see AccResultImportService) —
        // everyone else keeps the finishing position ACC's own leaderboard gave them and
        // goes through the normal position-based formula, same as a full classified finish.
        $finisherCount = collect($entries)->where('status', 'FIN')->count();
        \Log::info('RatingService: starting calculation', [
            'race_id'        => $race->id,
            'linked_drivers' => count($entries),
            'finishers'      => $finisherCount,
            'min_required'   => $this->calculator->MIN_DRIVERS,
        ]);

        // Priority: format multiplier > custom xcl_r_multiplier > legacy duration_key > 1.0 default
        $this->calculator->MULTIPLIER = $race->eventFormat?->xcl_r_multiplier
            ?? $race->xcl_r_multiplier
            ?? ($race->duration_key ? ($this->calculator->DURATION_MULTIPLIERS[$race->duration_key] ?? 1.0) : 1.0);

        try {
            $calculated = $this->calculator->processRace(
                ['name' => $race->title, 'race_date' => $race->scheduled_at->toDateString()],
                $entries
            );
        } catch (\InvalidArgumentException $e) {
            \Log::warning('RatingService: skipped — ' . $e->getMessage(), ['race_id' => $race->id]);
            return;
        }

        $byUserId = collect($calculated)->keyBy('driver_id');
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

        $byUserId->keys()->each(fn ($userId) => SyncDiscordRankRole::dispatch($userId));
    }

    private function ratingField(string $game): ?string
    {
        return match ($game) {
            'acc'     => 'elo_acc',
            'lmu'     => 'elo_lmu',
            'iracing' => 'elo_iracing',
            default   => null,
        };
    }

    private function srField(string $game): ?string
    {
        return match ($game) {
            'acc'     => 'sr_acc',
            'lmu'     => 'sr_lmu',
            'iracing' => 'sr_iracing',
            default   => null,
        };
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