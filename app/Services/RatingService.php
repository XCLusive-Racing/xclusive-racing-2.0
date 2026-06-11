<?php

namespace App\Services;

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
            $rating = (float) ($r->user->{$ratingField} ?? 1500);
            $status = $r->dns ? 'DNS' : ($r->dnf ? 'DNF' : 'FIN');

            return [
                'driver_id'  => $r->user_id,
                'name'       => $r->displayName(),
                'rating'     => $rating,
                'finish_pos' => ($status === 'FIN') ? $r->position : null,
                'status'     => $status,
            ];
        })->values()->all();

        $finisherCount = collect($entries)->where('status', 'FIN')->count();
        \Log::info('RatingService: starting calculation', [
            'race_id'        => $race->id,
            'linked_drivers' => count($entries),
            'finishers'      => $finisherCount,
            'min_required'   => $this->calculator->MIN_DRIVERS,
        ]);

        $this->calculator->MULTIPLIER = 1.0;

        if ($race->duration_key) {
            try {
                $this->calculator->setDuration($race->duration_key);
            } catch (\InvalidArgumentException) {
                // Unknown key — fall back to 1.0×
            }
        }

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

        DB::transaction(function () use ($results, $byUserId, $ratingField) {
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
            }
        });
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
}