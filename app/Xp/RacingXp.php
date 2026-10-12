<?php

namespace App\Xp;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\XpLedgerEntry;
use App\Models\XpRaceFact;

// The racing XP of one race result, from the "XP per race" rules (config/xp.php): a list
// of lines that add up, the multipliers on that total, and the one off bonus for a
// driver's first race in a title. The breakdown is what the ledger row keeps, so the
// reveal can show where the XP came from.
class RacingXp
{
    /** @return array{xp: int, breakdown: array}|null null for a driver who never started */
    public function for(User $user, Race $race, RaceResult $result): ?array
    {
        if ($result->dns) {
            return null;
        }

        $rules = config('xp.race');
        $game = Titles::game($race->game);
        $completed = ! $result->dnf && ! $result->dsq && ! $result->dc;
        $laps = (int) $result->lap_count;
        $lines = [];
        $add = function (string $key, string $label, int $xp, ?string $note = null) use (&$lines) {
            if ($xp > 0) {
                $lines[] = array_filter(['key' => $key, 'label' => $label, 'xp' => $xp, 'note' => $note], fn ($v) => $v !== null);
            }
        };

        $completed
            ? $add('completed', 'Race completed', $rules['completed'])
            : $add('retired', 'Race entered', $rules['retired']);
        $add('laps', $laps.' '.($laps === 1 ? 'lap' : 'laps').' completed', $laps * $rules['per_lap'], $laps.' x '.$rules['per_lap']);

        if ($completed) {
            $fact = XpRaceFact::where('race_result_id', $result->id)->first();
            if ($fact?->penalty_free && (int) $result->time_penalty_ms === 0) {
                $add('clean', 'Clean race, no penalties', $rules['clean']);
            }
            if ($fact?->all_laps_valid) {
                $add('all_laps_valid', 'All laps valid', $rules['all_laps_valid']);
            }

            $leaderLaps = (int) RaceResult::where('race_id', $race->id)->where('session_type', 'race')
                ->where('race_number', $result->race_number)->max('lap_count');
            if ($laps > 0 && $laps === $leaderLaps) {
                $add('lead_lap', 'Finished on the lead lap', $rules['lead_lap']);
            }

            foreach ($rules['positions'] as [$from, $to, $xp]) {
                if ($result->position >= $from && $result->position <= $to) {
                    $add('position', 'Position bonus, P'.$result->position, $xp);
                }
            }
        }

        $grid = RaceResult::where('race_id', $race->id)->where('session_type', 'quali')
            ->where('user_id', $user->id)->min('position');
        if ($grid !== null && (int) $grid === 1) {
            $add('pole', 'Pole position', $rules['pole']);
        }
        if ($result->fastest_lap) {
            $add('fastest_lap', 'Fastest lap', $rules['fastest_lap']);
        }
        if ($completed && $grid !== null && $grid > $result->position) {
            $gained = (int) $grid - (int) $result->position;
            $add('positions_gained', 'Positions gained', $gained * $rules['per_position_gained'], $gained.' x '.$rules['per_position_gained']);
        }
        if ($this->firstRaceOfTheDay($user, $race, $result)) {
            $add('first_race_of_day', 'First race of the day', $rules['first_race_of_day']);
        }

        $multipliers = array_filter([
            'endurance' => $race->is_endurance ? $rules['multipliers']['endurance'] : null,
            'championship' => $race->championship_id ? $rules['multipliers']['championship'] : null,
            'nordschleife' => Titles::isNordschleife($race->track) ? $rules['multipliers']['nordschleife'] : null,
            'supporter' => $user->isSupporter() ? $rules['multipliers']['supporter'] : null,
        ]);

        $subtotal = array_sum(array_column($lines, 'xp'));
        $total = (int) round($subtotal * array_product($multipliers ?: [1]));

        // Once per title, on top of the multiplied total.
        $newTitle = ! XpLedgerEntry::where('user_id', $user->id)->where('source', 'race')->where('game', $game)->exists();
        if ($newTitle) {
            $total += $rules['new_title'];
        }

        return [
            'xp' => $total,
            'breakdown' => [
                'lines' => $lines,
                'subtotal' => $subtotal,
                'multipliers' => $multipliers,
                'new_title_bonus' => $newTitle ? $rules['new_title'] : 0,
                'position' => (int) $result->position,
                'grid' => $grid !== null ? (int) $grid : null,
                'race_number' => (int) ($result->race_number ?? 1),
            ],
        ];
    }

    // The driver's earliest race start of that UK day (the first race of a multi-race round).
    private function firstRaceOfTheDay(User $user, Race $race, RaceResult $result): bool
    {
        if ((int) ($result->race_number ?? 1) !== 1 || ! $race->scheduled_at) {
            return false;
        }

        $dayStart = $race->scheduledAtUk()->startOfDay()->utc();

        return ! RaceResult::where('user_id', $user->id)->where('session_type', 'race')->where('dns', false)
            ->where('race_id', '!=', $race->id)
            ->where('race_scheduled_at', '>=', $dayStart)
            ->where('race_scheduled_at', '<', $race->scheduled_at)
            ->exists();
    }
}
