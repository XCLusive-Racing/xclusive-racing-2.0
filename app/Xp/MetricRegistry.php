<?php

namespace App\Xp;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\Membership;
use App\Models\MembershipPayment;
use App\Models\RacingTeam;
use App\Models\Report;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventResult;
use App\Models\TimeTrialLap;
use App\Models\User;
use App\Xp\Metrics\FactMetric;
use App\Xp\Metrics\Metric;
use App\Xp\Metrics\NotCountable;
use App\Xp\Metrics\RaceMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// Every metric an achievement can count. An achievement's metric_key must be one of
// these keys: the admin form only offers this list, because an achievement on a metric
// nobody computes would simply never be awarded.
//
// Adding a metric = adding one entry here (and, for a new kind of data, the code that
// reads it). Metrics the catalogue uses but the site can't count yet are listed at the
// bottom as NotCountable, with the reason.
class MetricRegistry
{
    /** @var array<string, Metric>|null */
    private ?array $metrics = null;

    /** @return array<string, Metric> */
    public function all(): array
    {
        return $this->metrics ??= collect([...$this->raceMetrics(), ...$this->factMetrics(), ...$this->notCountable()])
            ->keyBy(fn (Metric $m) => $m->key)->all();
    }

    public function get(string $key): ?Metric
    {
        return $this->all()[$key] ?? null;
    }

    /** @return array<string, Metric> */
    public function countable(): array
    {
        return array_filter($this->all(), fn (Metric $m) => $m->countable());
    }

    // ── Counted from race results ────────────────────────────────────────────────
    // $rows: the driver's race rows in the scope, oldest first (DriverHistory).

    private function raceMetrics(): array
    {
        $started = fn (Collection $rows) => $rows->where('started', true);
        $done = fn (Collection $rows) => $rows->where('completed', true);
        // The totals from before the website (users.legacy_*: races, wins, podiums) are added
        // in the all titles scope: they carry no title, date, track or car.
        $legacy = fn (User $user, string $scope, string $column) => $scope === 'all' ? (int) $user->{$column} : 0;
        $top = fn (int $n, string $column = 'legacy_podiums') => fn (Collection $rows, User $user, string $scope) => $done($rows)->where('position', '<=', $n)->count() + $legacy($user, $scope, $column);
        $flag = fn (bool $value) => $value ? 1 : 0;

        return [
            new RaceMetric('races_entered', 'Races entered', fn ($rows, User $user, string $scope) => $started($rows)->count() + $legacy($user, $scope, 'legacy_races')),
            new RaceMetric('races_completed', 'Races completed', fn ($rows, User $user, string $scope) => $done($rows)->count() + $legacy($user, $scope, 'legacy_races')),
            new RaceMetric('wins', 'Race wins', $top(1, 'legacy_wins')),
            new RaceMetric('podiums', 'Podium finishes', $top(3)),
            new RaceMetric('top5', 'Top five finishes', $top(5)),
            new RaceMetric('top10', 'Top ten finishes', $top(10)),
            new RaceMetric('top25', 'Top twenty five finishes', $top(25)),
            new RaceMetric('top50', 'Top fifty finishes', $top(50)),
            new RaceMetric('poles', 'Pole positions', fn ($rows) => $started($rows)->where('grid', 1)->count()),
            new RaceMetric('fastest_laps', 'Race fastest laps', fn ($rows) => $started($rows)->where('fastest_lap', true)->count()),
            new RaceMetric('hat_trick', 'Pole, fastest lap and win in one race',
                fn ($rows) => $flag($done($rows)->contains(fn ($r) => $r->position === 1 && $r->grid === 1 && $r->fastest_lap))),
            new RaceMetric('laps_completed', 'Racing laps completed', fn ($rows) => $started($rows)->sum('laps')),
            new RaceMetric('hours_raced', 'Hours spent in race sessions', fn ($rows) => floor($started($rows)->sum('total_time') / 3_600_000)),
            new RaceMetric('finish_streak', 'Longest run of races finished in a row',
                fn ($rows) => self::longestRun($started($rows), fn ($r) => $r->completed)),
            new RaceMetric('podium_streak', 'Longest run of podiums in a row',
                fn ($rows) => self::longestRun($started($rows), fn ($r) => $r->completed && $r->position <= 3)),
            new RaceMetric('weeks_active', 'Different weeks raced in',
                fn ($rows) => $started($rows)->map(fn ($r) => $r->at?->format('o-W'))->filter()->unique()->count()),
            // A car or circuit in two titles counts once per title.
            new RaceMetric('unique_cars', 'Different cars raced',
                fn ($rows) => $started($rows)->filter(fn ($r) => $r->vehicle)->map(fn ($r) => $r->game.'|'.$r->vehicle)->unique()->count()),
            new RaceMetric('unique_tracks', 'Different circuits raced',
                fn ($rows) => $started($rows)->filter(fn ($r) => $r->track)->map(fn ($r) => $r->game.'|'.$r->track)->unique()->count()),
            new RaceMetric('unique_formats', 'Different event formats raced',
                fn ($rows) => $started($rows)->pluck('format')->filter()->unique()->count()),
            new RaceMetric('all_classes', 'Raced in GT3, GT4, GT2, GTC and TCX',
                fn ($rows) => $flag($started($rows)->pluck('car_class')->intersect(['GT3', 'GT4', 'GT2', 'GTC', 'TCX'])->unique()->count() === 5)),
            new RaceMetric('endurance_debut', 'First endurance race completed', fn ($rows) => $done($rows)->where('endurance', true)->count()),
            new RaceMetric('endurance_races', 'Endurance races completed', fn ($rows) => $done($rows)->where('endurance', true)->count()),
            new RaceMetric('multiclass_debut', 'First multiclass race completed', fn ($rows) => $done($rows)->where('multiclass', true)->count()),
            new RaceMetric('driver_swap', 'Races completed sharing a car', fn ($rows) => $done($rows)->where('shared_car', true)->count()),
            new RaceMetric('comeback_kid', 'Finished ten or more places ahead of the grid slot',
                fn ($rows) => $flag($done($rows)->contains(fn ($r) => $r->grid && $r->grid - $r->position >= 10))),
            new RaceMetric('last_to_points', 'Started last and finished in the top ten',
                fn ($rows) => $flag($done($rows)->contains(fn ($r) => $r->grid_size > 10 && $r->grid === $r->grid_size && $r->position <= 10))),
            new RaceMetric('photo_finish', 'Won by less than a tenth of a second',
                fn ($rows) => $flag($done($rows)->contains(fn ($r) => $r->position === 1 && $r->gap_to_second_ms !== null && $r->gap_to_second_ms >= 0 && $r->gap_to_second_ms < 100))),
            new RaceMetric('iron_man', 'Raced in all three timezone blocks in one week', fn ($rows) => $flag(self::ironMan($started($rows)))),
            new RaceMetric('night_shift', 'Ten races starting between midnight and 6am local time', function ($rows, User $user) use ($done, $flag) {
                if (! $user->timezone) {
                    return null; // no local time without the driver's timezone
                }

                return $flag($done($rows)->filter(fn ($r) => $r->at && $r->at->copy()->timezone($user->timezone)->hour < 6)->count() >= 10);
            }),

            // Clean driving: from the race's stored results file (RaceFacts). Races without
            // one are unknown and count for nothing.
            new RaceMetric('clean_races', 'Races finished without a penalty', fn ($rows) => $done($rows)->where('clean', true)->count()),
            new RaceMetric('clean_race_streak', 'Longest run of races without a penalty',
                fn ($rows) => self::longestRun($done($rows), fn ($r) => $r->clean === true)),
            new RaceMetric('valid_lap_races', 'Races finished with every lap valid', fn ($rows) => $done($rows)->where('all_laps_valid', true)->count()),
            new RaceMetric('titles_clean', 'A clean race in three different titles',
                fn ($rows) => $flag($done($rows)->where('clean', true)->pluck('game')->unique()->count() >= 3)),

            // Cross title.
            new RaceMetric('unique_titles', 'Different titles raced', fn ($rows) => $done($rows)->pluck('game')->unique()->count()),
            new RaceMetric('unique_titles_won', 'Different titles won in', fn ($rows) => $done($rows)->where('position', 1)->pluck('game')->unique()->count()),
            new RaceMetric('titles_week', 'Three different titles raced in one week',
                fn ($rows) => $flag($started($rows)->groupBy(fn ($r) => $r->at?->format('o-W'))->contains(fn ($week) => $week->pluck('game')->unique()->count() >= 3))),
            new RaceMetric('cross_platform', 'Raced on both console and PC', fn ($rows) => $flag($started($rows)->pluck('platform')->unique()->count() >= 2)),

            // Nordschleife.
            new RaceMetric('nords_first', 'First Nordschleife race completed', fn ($rows) => $done($rows)->where('nordschleife', true)->count()),
            new RaceMetric('nords_races', 'Nordschleife races completed', fn ($rows) => $done($rows)->where('nordschleife', true)->count()),
            new RaceMetric('nords_laps', 'Nordschleife laps completed', fn ($rows) => $started($rows)->where('nordschleife', true)->sum('laps')),
            new RaceMetric('nords_endurance', 'Nordschleife endurance races completed',
                fn ($rows) => $done($rows)->where('nordschleife', true)->where('endurance', true)->count()),
            new RaceMetric('nords_clean_race', 'Nordschleife race with every lap valid and no penalty',
                fn ($rows) => $done($rows)->where('nordschleife', true)->where('clean', true)->where('all_laps_valid', true)->count()),

            // XCL Rating and Safety Rating: read, never changed here.
            new RaceMetric('xcl_rating', 'Highest XCL Rating reached', fn ($rows, User $user, string $scope) => max(
                (float) $rows->max('rating_after'),
                (float) collect(self::ratingGames($scope))->map(fn ($g) => $user->{User::eloColumn($g)})->max(),
            )),
            new RaceMetric('xclr_peak', 'Set a new career high XCL Rating', fn ($rows) => $flag(self::ratingClimb($rows) > 0)),
            new RaceMetric('xclr_climb_500', 'Gained 500 XCL Rating from the starting point', fn ($rows) => $flag(self::ratingClimb($rows) >= 500)),
            new RaceMetric('xclr_climb_1000', 'Gained 1000 XCL Rating from the starting point', fn ($rows) => $flag(self::ratingClimb($rows) >= 1000)),
            new RaceMetric('xclr_top100', 'In the top one hundred of the XCL Rating leaderboard', fn ($rows, User $user) => $flag(self::leaderboardPosition($rows, $user) <= 100)),
            new RaceMetric('xclr_top10', 'In the top ten of the XCL Rating leaderboard', fn ($rows, User $user) => $flag(self::leaderboardPosition($rows, $user) <= 10)),
            new RaceMetric('xclr_number_one', 'First on the XCL Rating leaderboard', fn ($rows, User $user) => $flag(self::leaderboardPosition($rows, $user) === 1)),
            new RaceMetric('safety_rating', 'Highest Safety Rating reached', $sr = fn ($rows, User $user, string $scope) => max(
                (float) $rows->max('sr_after'),
                // The rating a driver holds only counts once they have raced: everyone starts on 4.00.
                $started($rows)->isEmpty() ? 0.0 : (float) collect(self::ratingGames($scope))->map(fn ($g) => $user->{User::srColumn($g)})->max(),
            )),
            new RaceMetric('sr_max', 'Safety Rating of 9.50 or higher', fn ($rows, User $user, string $scope) => $flag($sr($rows, $user, $scope) >= 9.5)),
            new RaceMetric('redemption', 'Back to Safety Rating 5.00 after dropping below 3.00', function ($rows) {
                $fell = false;
                foreach ($rows->pluck('sr_after')->filter(fn ($sr) => $sr !== null) as $sr) {
                    if ($sr < 3.0) {
                        $fell = true;
                    } elseif ($fell && $sr >= 5.0) {
                        return 1;
                    }
                }

                return 0;
            }),

            // Stewarding, as far as it depends on the driver's races.
            new RaceMetric('clean_sheet', '25 races with no report filed against you', function ($rows, User $user) use ($done, $flag) {
                $reported = Report::where('reported_user_id', $user->id)->where('status', '!=', 'retracted')->pluck('race_id')->all();

                return $flag($done($rows)->filter(fn ($r) => $r->race_id && ! in_array($r->race_id, $reported))->count() >= 25);
            }),
        ];
    }

    // ── Counted from anything else ───────────────────────────────────────────────

    private function factMetrics(): array
    {
        $flag = fn (bool $value) => $value ? 1 : 0;
        $ttResults = fn (User $user) => TimeTrialEventResult::where('user_id', $user->id);
        $reports = fn (User $user) => Report::where('user_id', $user->id);
        $nordsEvents = fn () => TimeTrialEvent::where('track', 'nurburgring_24h')->pluck('id');

        return [
            new FactMetric('championships_entered', 'Championships entered', fn (User $user, DriverHistory $h, string $scope) => ChampionshipRegistration::approved()
                ->where('user_id', $user->id)->where('is_spectator', false)->pluck('championship_id')->unique()
                ->pipe(fn ($ids) => Championship::withoutTenantScope()->whereIn('id', $ids)->pluck('game'))
                ->filter(fn ($game) => $scope === 'all' || Titles::game($game) === $scope)->count()),

            new FactMetric('first_report', 'Incident reports submitted', fn (User $user) => $reports($user)->where('status', '!=', 'retracted')->count()),
            new FactMetric('reports_upheld', 'Incident reports upheld', fn (User $user) => $reports($user)->where('status', 'resolved')->count()),

            new FactMetric('discord_linked', 'Discord account linked', fn (User $user) => $flag($user->connectedAccounts()->where('provider', 'discord')->exists())),
            new FactMetric('profile_complete', 'Profile with an avatar and a platform linked', fn (User $user) => $flag(filled($user->banner) && filled($user->platform_id))),
            new FactMetric('first_team', 'Member of a racing team', fn (User $user) => $flag(
                DB::table('racing_team_members')->where('user_id', $user->id)->exists() || RacingTeam::where('owner_id', $user->id)->exists()
            )),
            new FactMetric('team_founder', 'Founded a racing team', fn (User $user) => $flag(RacingTeam::where('owner_id', $user->id)->exists())),
            new FactMetric('months_member', 'Months since the account was created', fn (User $user) => $user->created_at ? (int) $user->created_at->diffInMonths(now()) : 0),

            // Paying supporters only: a free or staff supporter has no record of when.
            new FactMetric('supporter_first', 'Supported XCLusive Racing (paid)', fn (User $user) => $flag(self::paidPayments($user)->exists())),
            new FactMetric('supporter_months', 'Months supported (paid)', fn (User $user) => self::paidPayments($user)->get(['plan'])
                ->sum(fn (MembershipPayment $p) => $p->plan === 'yearly' ? 12 : 1)),

            // Time trials are ACC.
            new FactMetric('tt_events', 'Time trial events with a time set', fn (User $user) => $ttResults($user)->count(), 'acc'),
            new FactMetric('tt_top10', 'Time trial events finished in the top ten', fn (User $user) => $ttResults($user)->where('position', '<=', 10)->count(), 'acc'),
            new FactMetric('tt_win', 'Weekly time trial events won', fn (User $user) => $ttResults($user)->where('position', 1)->count(), 'acc'),
            new FactMetric('nords_tt', 'Time trial lap set at the Nordschleife',
                fn (User $user) => $flag(TimeTrialLap::where('user_id', $user->id)->where('track', 'nurburgring_24h')->exists()), 'acc'),
            new FactMetric('nords_tt_top10', 'Nordschleife time trial finished in the top ten',
                fn (User $user) => $ttResults($user)->whereIn('time_trial_event_id', $nordsEvents())->where('position', '<=', 10)->count(), 'acc'),
        ];
    }

    // ── Used by the catalogue, not countable yet ─────────────────────────────────

    private function notCountable(): array
    {
        $standings = 'Final championship standings are not stored yet: they are computed live.';
        $laps = 'Needs lap by lap position or timing data that is not read from the results file yet.';
        $none = 'There is no system on the site that records this.';

        return [
            new NotCountable('championships_completed', 'Championship seasons completed', $standings),
            new NotCountable('championship_titles', 'Championship titles won', $standings),
            new NotCountable('champ_runner_up', 'Championship finished in second place', $standings),
            new NotCountable('champ_team_title', 'Team championship title won', $standings),
            new NotCountable('spotless_season', 'Championship season without a penalty', $standings),

            new NotCountable('lights_to_flag', 'Led every lap from pole', $laps),
            new NotCountable('wire_to_wire', 'Led from the first corner to the flag', $laps),
            new NotCountable('grand_slam', 'Pole, fastest lap, win and every lap led', $laps),
            new NotCountable('metronome', 'Every lap within a second of the average', $laps),
            new NotCountable('nords_best_lap', 'Best Nordschleife lap time', $laps),
            new NotCountable('nords_valid_streak', 'Valid Nordschleife laps in a row', $laps),

            new NotCountable('no_contact_races', 'Races with no reported contact', 'The game reports no contact data, and what counts as "reported" is not decided.'),
            new NotCountable('km_driven', 'Kilometres driven in competition', 'There is no table of circuit lengths.'),
            new NotCountable('nords_night', 'Nordschleife lap at night', 'Time of day is known per race, not per lap.'),
            new NotCountable('nords_wet', 'Nordschleife lap in the wet', 'Weather is known per race as configured, not as driven.'),
            new NotCountable('tt_pbs', 'Personal bests beaten in time trials', 'Only the current personal best is kept, not each time it was beaten.'),
            new NotCountable('tt_theoretical', 'Lap within a tenth of the theoretical best', 'Not computed from the stored sector times yet.'),
            new NotCountable('tt_all_tracks', 'Time trial lap on every circuit', 'There is no fixed list of time trial circuits to complete.'),

            new NotCountable('referrals', 'Drivers referred who completed a race', $none),
            new NotCountable('coaching_first', 'First coaching session completed', $none),
            new NotCountable('coaching_improve', 'Personal best improved after coaching', $none),
            new NotCountable('coaching_sessions', 'Coaching sessions completed', $none),
            new NotCountable('setup_download', 'Setup downloaded from the setup library', $none),
            new NotCountable('livery_upload', 'Custom livery uploaded', $none),
            new NotCountable('merch', 'Merchandise bought', $none),
            new NotCountable('member_tier', 'XCLusive Member subscription held', 'There is no Member tier, only the Supporter membership.'),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private static function longestRun(Collection $rows, callable $holds): int
    {
        $best = $run = 0;
        foreach ($rows as $row) {
            $run = $holds($row) ? $run + 1 : 0;
            $best = max($best, $run);
        }

        return $best;
    }

    // Europe, Australia and America evening blocks (as Race::eveningRegions()), all in one week.
    private static function ironMan(Collection $rows): bool
    {
        $zones = ['Europe/London', 'Australia/Sydney', 'America/New_York'];

        return $rows->filter(fn ($r) => $r->at)->groupBy(fn ($r) => $r->at->format('o-W'))->contains(function (Collection $week) use ($zones) {
            $hit = [];
            foreach ($week as $row) {
                foreach ($zones as $zone) {
                    if ($row->at->copy()->timezone($zone)->hour >= 14) {
                        $hit[$zone] = true;
                    }
                }
            }

            return count($hit) === count($zones);
        });
    }

    // How far the driver's rating has been above where it stood before their first rated race.
    private static function ratingClimb(Collection $rows): float
    {
        $rated = $rows->filter(fn ($r) => $r->rating_after !== null && $r->rating_before !== null);
        if ($rated->isEmpty()) {
            return 0.0;
        }

        return (float) $rated->max('rating_after') - (float) $rated->first()->rating_before;
    }

    // Best current leaderboard position over the titles the driver has rated races in
    // (PHP_INT_MAX without any). Filler accounts are not on the leaderboard.
    private static function leaderboardPosition(Collection $rows, User $user): int
    {
        $best = PHP_INT_MAX;
        foreach ($rows->filter(fn ($r) => $r->rating_after !== null)->pluck('game')->unique() as $game) {
            $column = User::eloColumn($game);
            if ($column) {
                $best = min($best, 1 + User::where('is_filler', false)->where($column, '>', (int) $user->{$column})->count());
            }
        }

        return $best;
    }

    private static function ratingGames(string $scope): array
    {
        return array_filter($scope === 'all' ? ['acc', 'lmu', 'iracing'] : [$scope], fn ($g) => User::eloColumn($g) !== null);
    }

    private static function paidPayments(User $user)
    {
        return MembershipPayment::where('user_id', $user->id)->where('status', 'paid')
            ->where(fn ($q) => $q->whereNull('mode')->orWhere('mode', Membership::currentMode()));
    }
}
