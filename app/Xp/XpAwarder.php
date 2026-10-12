<?php

namespace App\Xp;

use App\Models\Achievement;
use App\Models\AchievementAward;
use App\Models\Game;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Models\UserMetric;
use App\Models\XpLedgerEntry;
use App\Models\XpRankUp;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

// Awards XP: racing XP for a race, achievements whose threshold is now met, and manual
// adjustments. Everything it grants is a row in the ledger; nothing is ever taken away.
//
// Safe to run twice: racing XP is unique per driver and race (xp_ledger.reference_key),
// an achievement is unique per driver (achievement_awards), and both are checked inside a
// transaction that holds a lock on the driver.
class XpAwarder
{
    public function __construct(
        private MetricRegistry $metrics,
        private RankCurve $curve,
        private RacingXp $racingXp,
        private RaceFacts $facts,
    ) {}

    /** @return array{race_xp: int, awards: int, rank_before: int, rank_after: int} */
    public function processRace(User $user, Race $race): array
    {
        // A race from before the launch earns no racing XP (its results still count as history).
        if (! Launch::counts($race->scheduled_at)) {
            return ['race_xp' => 0, 'awards' => 0, 'rank_before' => (int) $user->xp_rank, 'rank_after' => (int) $user->xp_rank];
        }

        return DB::transaction(function () use ($user, $race) {
            $driver = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $rankBefore = (int) $driver->xp_rank;
            $game = Titles::game($race->game);
            $raceXp = 0;

            $results = RaceResult::where('race_id', $race->id)->where('user_id', $driver->id)
                ->where('session_type', 'race')->orderBy('race_number')->get();

            foreach ($results as $result) {
                $raceNumber = (int) ($result->race_number ?? 1);
                $key = "race:{$race->id}:{$raceNumber}";
                if (XpLedgerEntry::where('user_id', $driver->id)->where('reference_key', $key)->exists()) {
                    continue;
                }

                $this->facts->store($race, $raceNumber);
                $earned = $this->racingXp->for($driver, $race, $result);
                if (! $earned || $earned['xp'] <= 0) {
                    continue;
                }

                $written = $this->write($driver, [
                    'xp' => $earned['xp'],
                    'source' => 'race',
                    'game' => $game,
                    'platform' => Titles::platform($race->game),
                    'reference_type' => 'race',
                    'reference_id' => $race->id,
                    'reference_key' => $key,
                    'reason' => trim($race->title.' at '.$race->track).($race->raceCount() > 1 ? ', race '.$raceNumber : ''),
                    'breakdown' => $earned['breakdown'],
                ]);
                $raceXp += $written ? $earned['xp'] : 0;
            }

            $awards = $this->evaluate($driver, $game, $race);
            $rankAfter = $this->refreshTotals($driver, $race);

            return ['race_xp' => $raceXp, 'awards' => $awards, 'rank_before' => $rankBefore, 'rank_after' => $rankAfter];
        });
    }

    // Recomputes the driver's metrics and awards every unarchived achievement that is now
    // met and not yet held. With a title, in the scopes that title can have moved ('all'
    // and its own); without one, in every scope. Returns how many were awarded.
    public function evaluate(User $driver, ?string $game = null, ?Race $race = null): int
    {
        DriverHistory::forget($driver->id);

        $scopes = $game ? ['all', $game] : ['all', ...Game::pluck('key')->all()];
        $values = [];

        foreach ($this->metrics->countable() as $key => $metric) {
            foreach ($scopes as $scope) {
                $value = $metric->value($driver, $scope);
                if ($value === null) {
                    continue;
                }

                $values[$key][$scope] = $value;
                UserMetric::updateOrCreate(
                    ['user_id' => $driver->id, 'metric_key' => $key, 'scope' => $scope],
                    ['value' => $value],
                );
            }
        }

        $held = AchievementAward::where('user_id', $driver->id)->pluck('achievement_id');
        $candidates = Achievement::active()->whereIn('game_scope', $scopes)->whereNotIn('id', $held)
            ->orderBy('sort_order')->get();
        $awarded = 0;

        foreach ($candidates as $achievement) {
            $value = $values[$achievement->metric_key][$achievement->game_scope] ?? null;

            // Null: the metric can't be counted (yet, or for this driver). Never an award.
            if ($value === null || ! $achievement->isMetBy($value)) {
                continue;
            }

            $awarded += $this->award($driver, $achievement, $achievement->game_scope === Achievement::SCOPE_ALL ? $game : $achievement->game_scope, $race) ? 1 : 0;
        }

        DriverHistory::forget($driver->id);

        return $awarded;
    }

    // The award and its XP are written together or not at all.
    private function award(User $driver, Achievement $achievement, ?string $game, ?Race $race): bool
    {
        try {
            return DB::transaction(function () use ($driver, $achievement, $game, $race) {
                AchievementAward::create([
                    'user_id' => $driver->id,
                    'achievement_id' => $achievement->id,
                    'xp_awarded' => $achievement->xp,
                    'game' => $game,
                    'race_id' => $race?->id,
                    'source_type' => $race ? 'race' : null,
                    'source_id' => $race?->id,
                    'awarded_at' => now(),
                ]);

                XpLedgerEntry::create([
                    'user_id' => $driver->id,
                    'xp' => $achievement->xp,
                    'source' => 'achievement',
                    'game' => $game,
                    'platform' => $race ? Titles::platform($race->game) : null,
                    'reference_type' => 'achievement',
                    'reference_id' => $achievement->id,
                    'reference_key' => 'achievement:'.$achievement->id,
                    'reason' => 'Achievement: '.$achievement->name,
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return false; // already held
        }
    }

    // A manual grant by an admin. Positive only: there is no way to take XP away.
    public function adjust(User $user, int $xp, string $reason, ?User $by = null): XpLedgerEntry
    {
        abort_if($xp <= 0, 422, 'An adjustment can only add XP.');

        return DB::transaction(function () use ($user, $xp, $reason, $by) {
            $driver = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $entry = XpLedgerEntry::create([
                'user_id' => $driver->id,
                'xp' => $xp,
                'source' => 'adjustment',
                'reference_type' => $by ? 'user' : null,
                'reference_id' => $by?->id,
                'reason' => $reason,
            ]);

            $this->refreshTotals($driver);

            return $entry;
        });
    }

    // Rebuilds the driver's cached total and rank from the ledger, and records a rank up.
    // Returns the rank.
    public function refreshTotals(User $driver, ?Race $race = null): int
    {
        $total = (int) XpLedgerEntry::where('user_id', $driver->id)->sum('xp');
        $rank = $this->curve->rankFor($total);
        $before = (int) User::whereKey($driver->id)->value('xp_rank');

        if ($rank > $before) {
            XpRankUp::create(['user_id' => $driver->id, 'from_rank' => $before, 'to_rank' => $rank, 'race_id' => $race?->id]);
        }

        User::whereKey($driver->id)->update(['xp_total' => $total, 'xp_rank' => $rank]);
        $driver->forceFill(['xp_total' => $total, 'xp_rank' => $rank])->syncOriginal();

        return $rank;
    }

    private function write(User $driver, array $entry): bool
    {
        try {
            // A savepoint, so a duplicate leaves the surrounding transaction usable.
            DB::transaction(fn () => XpLedgerEntry::create(['user_id' => $driver->id] + $entry));

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
