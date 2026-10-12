<?php

namespace Tests\Feature\Xp;

use App\Jobs\AwardRaceXp;
use App\Models\Achievement;
use App\Models\AchievementAward;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RaceSessionFile;
use App\Models\User;
use App\Models\UserMetric;
use App\Models\XpLedgerEntry;
use App\Models\XpRankUp;
use App\Xp\RankCurve;
use App\Xp\XpAwarder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use LogicException;
use Tests\TestCase;

// The awarding engine (docs/xp-achievements/PLAN.md, phase one): racing XP and
// achievements, written to the ledger, never twice and never taken back.
class XpAwardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xp.enabled' => true, 'xp.launch_at' => now()->subYear()->toDateTimeString()]);

        // Each test works with its own achievements, not the 399 seeded ones.
        Achievement::query()->update(['is_archived' => true]);
    }

    private function driver(): User
    {
        return User::factory()->create(['elo_acc' => 1500, 'elo_lmu' => 1500, 'elo_iracing' => 1500, 'sr_acc' => 4, 'sr_lmu' => 4, 'sr_iracing' => 4]);
    }

    private function achievement(string $metric, float $threshold, string $scope = 'all', int $xp = 500): Achievement
    {
        return Achievement::create([
            'code' => "test_{$metric}_{$scope}_".(int) $threshold, 'name' => "Test {$metric}", 'category' => 'Test',
            'metric_key' => $metric, 'game_scope' => $scope, 'threshold' => $threshold, 'xp' => $xp,
        ]);
    }

    // A finished race in $game with $user in $position (of three cars), ten laps.
    private function race(User $user, string $game = 'acc', int $position = 1, array $attributes = []): Race
    {
        $race = Race::create($attributes + [
            'title' => 'Sprint Race', 'track' => 'Monza', 'game' => $game, 'status' => 'finished',
            'scheduled_at' => now()->subDays(Race::count() + 1),
        ]);

        foreach ([1, 2, 3] as $place) {
            $driver = $place === $position ? $user : User::factory()->create();
            RaceResult::create([
                'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => $race->track, 'race_game' => $game,
                'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
                'user_id' => $driver->id, 'player_id' => 'P'.$driver->id, 'driver_name' => $driver->name,
                'car_number' => $place, 'position' => $place, 'lap_count' => 10, 'total_time' => 1_200_000 + $place * 5000,
                'dnf' => false, 'dns' => false,
            ]);
        }

        return $race;
    }

    private function process(User $user, Race $race): array
    {
        return app(XpAwarder::class)->processRace($user, $race);
    }

    public function test_a_race_writes_its_racing_xp_once_with_the_breakdown(): void
    {
        $user = $this->driver();
        $race = $this->race($user);

        $this->process($user, $race);

        $entry = XpLedgerEntry::where('user_id', $user->id)->where('source', 'race')->sole();
        // 600 finished + 10 laps x 25 + 100 lead lap + 1000 win + 300 first race of the day,
        // then 1000 once for a first race in the title.
        $this->assertSame(3250, $entry->xp);
        $this->assertSame('acc', $entry->game);
        $this->assertSame('console', $entry->platform);
        $this->assertSame(2250, $entry->breakdown['subtotal']);
        $this->assertSame(1000, $entry->breakdown['new_title_bonus']);
        $this->assertSame(['completed', 'laps', 'lead_lap', 'position', 'first_race_of_day'], array_column($entry->breakdown['lines'], 'key'));

        // A second race in the same title: no new title bonus.
        $this->process($user, $second = $this->race($user));
        $this->assertSame(2250, XpLedgerEntry::where('reference_key', "race:{$second->id}:1")->value('xp'));
    }

    public function test_the_stored_results_file_adds_clean_race_and_valid_lap_xp(): void
    {
        $user = $this->driver();
        $race = $this->race($user);
        RaceSessionFile::put($race, 1, json_encode([
            'sessionType' => 'R',
            'sessionResult' => ['leaderBoardLines' => [['car' => ['carId' => 1001, 'drivers' => [['playerId' => 'P'.$user->id]]]]]],
            'laps' => [['carId' => 1001, 'isValidForBest' => true], ['carId' => 1001, 'isValidForBest' => true]],
            'penalties' => [['carId' => 1002, 'penalty' => 'DriveThrough']],
        ]));

        $this->process($user, $race);

        $keys = array_column(XpLedgerEntry::where('user_id', $user->id)->sole()->breakdown['lines'], 'key');
        $this->assertContains('clean', $keys);
        $this->assertContains('all_laps_valid', $keys);
        $this->assertSame(1.0, UserMetric::where(['user_id' => $user->id, 'metric_key' => 'clean_races', 'scope' => 'all'])->value('value'));
    }

    public function test_format_and_title_multipliers_apply_to_the_race_total(): void
    {
        $user = $this->driver();
        $race = $this->race($user, 'acc', 1, ['track' => 'Nordschleife', 'is_endurance' => true]);

        $this->process($user, $race);

        $entry = XpLedgerEntry::where('user_id', $user->id)->sole();
        $this->assertSame(['endurance' => 1.5, 'nordschleife' => 1.5], $entry->breakdown['multipliers']);
        $this->assertSame((int) round(2250 * 1.5 * 1.5) + 1000, $entry->xp);
    }

    public function test_the_same_race_processed_twice_awards_nothing_the_second_time(): void
    {
        $user = $this->driver();
        $this->achievement('wins', 1);
        $race = $this->race($user);

        $first = $this->process($user, $race);
        $this->assertSame(1, $first['awards']);
        $ledger = XpLedgerEntry::where('user_id', $user->id)->count();
        $total = $user->fresh()->xp_total;

        $second = $this->process($user, $race);

        $this->assertSame(['race_xp' => 0, 'awards' => 0], ['race_xp' => $second['race_xp'], 'awards' => $second['awards']]);
        $this->assertSame($ledger, XpLedgerEntry::where('user_id', $user->id)->count());
        $this->assertSame(1, AchievementAward::where('user_id', $user->id)->count());
        $this->assertSame($total, $user->fresh()->xp_total);
    }

    public function test_the_total_is_the_ledger_sum_and_the_rank_is_the_curve_lookup(): void
    {
        $user = $this->driver();
        $this->achievement('wins', 1, 'all', 700);

        $this->process($user, $this->race($user));
        $this->process($user, $this->race($user, 'acc', 2));
        app(XpAwarder::class)->adjust($user, 40000, 'Event marshal for a season');

        $user->refresh();
        $sum = (int) XpLedgerEntry::where('user_id', $user->id)->sum('xp');

        $this->assertSame($sum, $user->xp_total);
        $this->assertSame(app(RankCurve::class)->rankFor($sum), $user->xp_rank);
        $this->assertGreaterThan(1, $user->xp_rank);

        // Every rank gained is recorded, for the reveal.
        $rankUps = XpRankUp::where('user_id', $user->id)->orderBy('id')->get();
        $this->assertSame(1, $rankUps->first()->from_rank);
        $this->assertSame($user->xp_rank, $rankUps->last()->to_rank);
    }

    public function test_an_archived_achievement_is_never_awarded_but_stays_held(): void
    {
        $holder = $this->driver();
        $achievement = $this->achievement('wins', 1);
        $this->process($holder, $this->race($holder));
        $this->assertSame(1, AchievementAward::where('user_id', $holder->id)->count());

        $achievement->update(['is_archived' => true]);

        $later = $this->driver();
        $this->process($later, $this->race($later));

        $this->assertSame(0, AchievementAward::where('user_id', $later->id)->count());
        $this->assertSame(500, AchievementAward::where('user_id', $holder->id)->sole()->xp_awarded);
        $this->assertSame(500, XpLedgerEntry::where('user_id', $holder->id)->where('source', 'achievement')->sole()->xp);
    }

    public function test_editing_an_achievements_xp_changes_future_awards_only(): void
    {
        $first = $this->driver();
        $achievement = $this->achievement('wins', 1, 'all', 500);
        $this->process($first, $this->race($first));
        $totalBefore = $first->fresh()->xp_total;

        $achievement->update(['xp' => 9000]);

        $second = $this->driver();
        $this->process($second, $this->race($second));

        $this->assertSame(500, AchievementAward::where('user_id', $first->id)->sole()->xp_awarded);
        $this->assertSame($totalBefore, $first->fresh()->xp_total);
        $this->assertSame(9000, AchievementAward::where('user_id', $second->id)->sole()->xp_awarded);
    }

    public function test_a_metric_that_cannot_be_counted_never_awards(): void
    {
        $user = $this->driver();
        // Not countable yet (no referral system), unknown to the registry, and a threshold of zero.
        $this->achievement('referrals', 0);
        $this->achievement('not_a_metric', 0);

        $this->process($user, $this->race($user));

        $this->assertSame(0, AchievementAward::where('user_id', $user->id)->count());
        $this->assertSame(0, UserMetric::where('user_id', $user->id)->whereIn('metric_key', ['referrals', 'not_a_metric'])->count());
    }

    public function test_a_title_scoped_achievement_needs_progress_in_that_title(): void
    {
        $user = $this->driver();
        $lmu = $this->achievement('wins', 1, 'lmu');
        $acc = $this->achievement('wins', 2, 'acc');

        $this->process($user, $this->race($user, 'acc'));
        $this->assertSame(0, AchievementAward::where('user_id', $user->id)->count());

        // ACC on PC is the same title as ACC on console: the second ACC win counts.
        $this->process($user, $this->race($user, 'ac'));
        $this->assertSame([$acc->id], AchievementAward::where('user_id', $user->id)->pluck('achievement_id')->all());
        $this->assertSame(0.0, (float) UserMetric::where(['user_id' => $user->id, 'metric_key' => 'wins', 'scope' => 'acc'])->value('value') - 2);

        $this->process($user, $this->race($user, 'lmu'));
        $this->assertTrue(AchievementAward::where('user_id', $user->id)->where('achievement_id', $lmu->id)->where('game', 'lmu')->exists());
    }

    public function test_a_title_agnostic_achievement_counts_progress_in_any_title(): void
    {
        $user = $this->driver();
        $wins = $this->achievement('wins', 3);
        $titles = $this->achievement('unique_titles', 3);
        $both = $this->achievement('cross_platform', 1);

        $this->process($user, $this->race($user, 'acc'));
        $this->process($user, $this->race($user, 'lmu'));
        $this->assertSame(0, AchievementAward::where('user_id', $user->id)->whereIn('achievement_id', [$wins->id, $titles->id])->count());
        // Console (ACC) and PC (LMU) raced.
        $this->assertTrue(AchievementAward::where('user_id', $user->id)->where('achievement_id', $both->id)->exists());

        $this->process($user, $this->race($user, 'iracing'));

        $this->assertEqualsCanonicalizing(
            [$wins->id, $titles->id, $both->id],
            AchievementAward::where('user_id', $user->id)->pluck('achievement_id')->all(),
        );
        // The award remembers the title that tipped it over.
        $this->assertSame('iracing', AchievementAward::where('achievement_id', $wins->id)->value('game'));
    }

    public function test_a_circuit_in_two_titles_counts_once_per_title(): void
    {
        $user = $this->driver();
        $this->process($user, $this->race($user, 'acc', 1, ['track' => 'Spa']));
        $this->process($user, $this->race($user, 'ac', 1, ['track' => 'Spa-Francorchamps']));
        $this->process($user, $this->race($user, 'lmu', 1, ['track' => 'Spa']));

        $value = fn (string $scope) => (float) UserMetric::where(['user_id' => $user->id, 'metric_key' => 'unique_tracks', 'scope' => $scope])->value('value');

        $this->assertSame(2.0, $value('all'));   // Spa in ACC (either platform), Spa in LMU
        $this->assertSame(1.0, $value('lmu'));
    }

    public function test_the_ledger_cannot_be_rewritten_or_emptied(): void
    {
        $user = $this->driver();
        $entry = app(XpAwarder::class)->adjust($user, 100, 'Test');

        $entry->update(['seen_at' => now()]); // marking it seen is fine

        try {
            $entry->update(['xp' => 1]);
            $this->fail('The ledger accepted a changed amount.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $entry->delete();
    }

    public function test_everybody_starts_from_zero_at_the_launch(): void
    {
        Bus::fake();
        $user = $this->driver();
        $wins = $this->achievement('wins', 2);
        $before = $this->race($user, 'acc', 1, ['scheduled_at' => now()->subDays(20)]);
        $after = $this->race($user, 'acc', 1, ['scheduled_at' => now()->subDays(5)]);
        config(['xp.launch_at' => now()->subDays(10)->toDateTimeString()]);

        // A race from before the launch: no job, no XP, and it is not counted either.
        $this->assertSame(0, AwardRaceXp::dispatchForRace($before));
        $this->assertSame(0, $this->process($user, $before)['race_xp']);
        $this->assertSame(0, XpLedgerEntry::count());

        // The first race after it is the driver's first: one win, new title bonus and all.
        $this->assertSame(3, AwardRaceXp::dispatchForRace($after));
        $this->assertSame(3250, $this->process($user, $after)['race_xp']);
        $this->assertSame(1.0, (float) UserMetric::where(['user_id' => $user->id, 'metric_key' => 'wins', 'scope' => 'all'])->value('value'));
        $this->assertFalse(AchievementAward::where('achievement_id', $wins->id)->exists());

        $this->process($user, $this->race($user, 'acc', 1, ['scheduled_at' => now()->subDays(3)]));
        $this->assertTrue(AchievementAward::where('achievement_id', $wins->id)->exists());
    }

    public function test_a_driver_alone_on_track_earns_nothing(): void
    {
        $user = $this->driver();
        $entered = $this->achievement('races_entered', 1);
        $race = $this->race($user);
        RaceResult::where('race_id', $race->id)->where('user_id', '!=', $user->id)->update(['dns' => true]);

        $this->assertSame(0, $this->process($user, $race)['race_xp']);
        $this->assertSame(0, XpLedgerEntry::count());
        $this->assertFalse(AchievementAward::where('achievement_id', $entered->id)->exists());

        // Two drivers on track is a race.
        $this->assertGreaterThan(0, $this->process($user, $this->race($user))['race_xp']);
        $this->assertTrue(AchievementAward::where('achievement_id', $entered->id)->exists());
    }

    public function test_without_a_launch_date_nothing_is_awarded(): void
    {
        Bus::fake();
        config(['xp.launch_at' => null]);
        $user = $this->driver();
        $race = $this->race($user);

        $this->assertSame(0, AwardRaceXp::dispatchForRace($race));
        $this->assertSame(0, $this->process($user, $race)['race_xp']);
        $this->assertSame(0, XpLedgerEntry::count());
        $this->assertSame(0, $user->fresh()->xp_total);
    }

    public function test_jobs_are_queued_per_linked_driver_and_only_while_xp_is_enabled(): void
    {
        Bus::fake();
        $user = $this->driver();
        $race = $this->race($user);
        RaceResult::where('race_id', $race->id)->where('position', 3)->update(['user_id' => null]);

        $this->assertSame(2, AwardRaceXp::dispatchForRace($race));
        Bus::assertDispatchedTimes(AwardRaceXp::class, 2);

        config(['xp.enabled' => false]);
        $this->assertSame(0, AwardRaceXp::dispatchForRace($race));
        Bus::assertDispatchedTimes(AwardRaceXp::class, 2);

        // And a job that was already queued does nothing either.
        (new AwardRaceXp($race->id, $user->id))->handle(app(XpAwarder::class));
        $this->assertSame(0, XpLedgerEntry::count());
    }
}
