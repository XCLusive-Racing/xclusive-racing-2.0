<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Services\EntryBalanceService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback (NLRL 2026-09, Sunday League 2026-09-27): success ballast from
// finishing positions, pushed as the entrylist's ballastKg together with the
// championship's manual per-driver/team adjustments — either for the next round
// only or built up over the season.
class SuccessBallastTest extends TestCase
{
    use RefreshDatabase;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();

        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $this->setBalance(['success_ballast_enabled' => true, 'success_ballast_kg' => '30, 20, 10, 0']);
    }

    private function setBalance(array $balance): void
    {
        $this->championship->settings = array_replace_recursive($this->championship->settings->toArray(), ['balance' => $balance]);
        $this->championship->save();
    }

    // Sunday League's setup: +5..-5 per position, built up over the season.
    private function cumulative(array $balance = []): void
    {
        $this->setBalance(array_merge([
            'success_ballast_mode' => 'cumulative',
            'success_ballast_kg' => '5, 3, 2, 1, 0, -1, -2, -3, -4, -5',
            'success_ballast_starting' => '',
        ], $balance));
    }

    private function makeRound(int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $this->championship->id, 'round_number' => $number,
            'title' => 'Round '.$number, 'track' => 'monza', 'game' => 'acc',
            'status' => $status, 'scheduled_at' => now()->addWeeks($number),
        ]);
    }

    private function finish(Race $race, User $user, int $position, array $extra = []): RaceResult
    {
        return RaceResult::create(array_merge([
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id, 'player_id' => $user->platform_id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20, 'total_time' => 1_200_000 + $position,
            'car_number' => $user->id, 'car_class' => 'GT3',
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ], $extra));
    }

    /** Finishes a round with $users in that order (P1 first). */
    private function finishRound(int $number, array $users): Race
    {
        $round = $this->makeRound($number, 'finished');
        foreach (array_values($users) as $i => $user) {
            $this->finish($round, $user, $i + 1);
        }

        return $round;
    }

    private function driver(): User
    {
        return User::factory()->create(['platform' => 'xbox', 'platform_id' => 'M'.fake()->unique()->numerify('################'), 'team' => null]);
    }

    private function drivers(int $count): array
    {
        return array_map(fn () => $this->driver(), range(1, $count));
    }

    private function ballastInto(int $round, User $user): int
    {
        return app(EntryBalanceService::class)->ballastInto($this->makeRound($round), $user->id);
    }

    /** ballastKg per driver name on a round's entrylist. */
    private function ballastOnEntryList(Race $race): array
    {
        return collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->mapWithKeys(fn ($entry) => [$entry['drivers'][0]['lastName'] => $entry['ballastKg']])
            ->all();
    }

    public function test_next_round_mode_uses_the_last_round_raced_and_the_last_value_covers_lower_positions(): void
    {
        [$first, $second, $third, $fourth, $fifth, $retired] = $this->drivers(6);

        $round1 = $this->finishRound(1, [$first, $second, $third, $fourth]);
        $this->setBalance(['success_ballast_kg' => '30, 20, 10']);
        $this->finish($round1, $fifth, 5);
        // Out on lap 0: under the 1-lap minimum, so no change.
        $this->finish($round1, $retired, 6, ['dnf' => true, 'lap_count' => 0]);

        $round2 = $this->makeRound(2);
        foreach ([$first, $second, $third, $fourth, $fifth, $retired] as $user) {
            RaceRegistration::create(['race_id' => $round2->id, 'user_id' => $user->id]);
        }

        $this->assertSame([
            $first->name => 30, $second->name => 20, $third->name => 10,
            $fourth->name => 10, $fifth->name => 10, $retired->name => 0,
        ], $this->ballastOnEntryList($round2));

        // Round 1 itself carries nothing.
        RaceRegistration::create(['race_id' => $round1->id, 'user_id' => $first->id]);
        $this->assertSame([$first->name => 0], $this->ballastOnEntryList($round1));

        // Round 3: no build-up for who raced round 2, and $first missing round 2
        // keeps round 1's ballast rather than dropping to 0.
        $round2->update(['status' => 'finished']);
        $this->finish($round2, $fourth, 1);
        $this->finish($round2, $second, 2);

        $this->assertSame(30, $this->ballastInto(3, $fourth));
        $this->assertSame(20, $this->ballastInto(3, $second));
        $this->assertSame(30, $this->ballastInto(3, $first));
    }

    public function test_multiclass_ranks_within_each_class_and_a_team_car_shares_its_ballast(): void
    {
        $this->championship->update(['is_multiclass' => true]);
        [$gt3Winner, $gt3Second, $gt4WinnerA, $gt4WinnerB] = $this->drivers(4);

        $round1 = $this->makeRound(1, 'finished');
        $this->finish($round1, $gt3Winner, 1, ['car_number' => 1]);
        $this->finish($round1, $gt3Second, 2, ['car_number' => 2]);
        // One GT4 team car, two drivers — first in its class.
        $this->finish($round1, $gt4WinnerA, 3, ['car_number' => 40, 'car_class' => 'GT4']);
        $this->finish($round1, $gt4WinnerB, 3, ['car_number' => 40, 'car_class' => 'GT4']);

        $ballast = app(EntryBalanceService::class)->successBallast($this->makeRound(2));
        ksort($ballast);

        $this->assertSame(
            [$gt3Winner->id => 30, $gt3Second->id => 20, $gt4WinnerA->id => 30, $gt4WinnerB->id => 30],
            $ballast
        );
    }

    public function test_manual_driver_adjustments_apply_on_top_and_cap_at_the_acc_limits(): void
    {
        $winner = $this->driver();
        $this->setBalance(['adjustments' => [
            ['scope' => 'driver', 'target' => $winner->displayName(), 'ballast_kg' => 90, 'restrictor_percent' => 25],
        ]]);

        $this->finishRound(1, [$winner]);
        $round2 = $this->makeRound(2);
        RaceRegistration::create(['race_id' => $round2->id, 'user_id' => $winner->id]);

        $entry = app(AccServerConfigService::class)->entryList($round2)['entries'][0];
        $this->assertSame(40, $entry['ballastKg']);
        $this->assertSame(20, $entry['restrictor']);
    }

    public function test_cumulative_mode_builds_up_stops_at_the_cap_and_goes_below_zero(): void
    {
        $this->cumulative(['success_ballast_cap' => 8]);
        [$winner, $loser] = $this->drivers(2);
        $field = $this->drivers(8);

        // $winner wins twice (5 + 5 = 10, capped at 8); $loser is P10 twice (-5 - 5).
        $this->finishRound(1, [$winner, ...$field, $loser]);
        $this->finishRound(2, [$winner, ...$field, $loser]);

        $this->assertSame(8, $this->ballastInto(3, $winner));
        $this->assertSame(-10, $this->ballastInto(3, $loser));
        // $field[0] was P2 twice: 3 + 3.
        $this->assertSame(6, $this->ballastInto(3, $field[0]));

        $history = app(EntryBalanceService::class)->history($this->championship);
        $this->assertSame(EntryBalanceService::REASON_CAPPED, $history[$winner->id][1]['reason']);
    }

    public function test_ballast_never_goes_below_acc_minimum_of_minus_40(): void
    {
        $this->cumulative();
        [$loser] = $this->drivers(1);
        $field = $this->drivers(9);

        foreach (range(1, 9) as $round) {
            $this->finishRound($round, [...$field, $loser]);
        }

        // 9 × -5 = -45, stopped at ACC's -40.
        $this->assertSame(-40, $this->ballastInto(10, $loser));
        $history = app(EntryBalanceService::class)->history($this->championship);
        $this->assertSame(EntryBalanceService::REASON_FLOORED, end($history[$loser->id])['reason']);

        $round10 = $this->makeRound(10);
        RaceRegistration::create(['race_id' => $round10->id, 'user_id' => $loser->id]);
        $this->assertSame([$loser->name => -40], $this->ballastOnEntryList($round10));
    }

    public function test_a_multi_race_round_counts_the_best_result_once(): void
    {
        $this->cumulative();
        [$driver, $rival] = $this->drivers(2);

        $round = $this->makeRound(1, 'finished');
        $this->finish($round, $driver, 1, ['race_number' => 1]);
        $this->finish($round, $rival, 2, ['race_number' => 1]);
        $this->finish($round, $rival, 1, ['race_number' => 2]);
        $this->finish($round, $driver, 2, ['race_number' => 2]);

        // Both drivers' best is P1: +5 once, not +5 +3.
        $this->assertSame(5, $this->ballastInto(2, $driver));
        $this->assertSame(5, $this->ballastInto(2, $rival));
    }

    public function test_missed_rounds_and_too_few_laps_leave_the_ballast_unchanged(): void
    {
        $this->cumulative(['success_ballast_min_laps' => 5]);
        [$driver, $other] = $this->drivers(2);

        $this->finishRound(1, [$driver, $other]);                   // +5
        $this->finishRound(2, [$other]);                            // missed
        $round3 = $this->makeRound(3, 'finished');
        $this->finish($round3, $driver, 1, ['lap_count' => 3]);     // under 5 laps
        $this->finish($round3, $other, 2);

        $this->assertSame(5, $this->ballastInto(4, $driver));

        $reasons = collect(app(EntryBalanceService::class)->history($this->championship)[$driver->id])->pluck('reason')->all();
        $this->assertSame([
            EntryBalanceService::REASON_NORMAL, EntryBalanceService::REASON_MISSED, EntryBalanceService::REASON_UNDER_MIN_LAPS,
        ], $reasons);
    }

    public function test_with_several_races_the_best_race_with_enough_laps_counts(): void
    {
        $this->cumulative(['success_ballast_min_laps' => 5]);
        [$driver, $rival] = $this->drivers(2);

        $round = $this->makeRound(1, 'finished');
        $this->finish($round, $driver, 1, ['race_number' => 1, 'lap_count' => 2]);
        $this->finish($round, $rival, 2, ['race_number' => 1]);
        $this->finish($round, $rival, 1, ['race_number' => 2]);
        $this->finish($round, $driver, 2, ['race_number' => 2]);

        // Race 1's P1 was under the minimum, so race 2's P2 counts: +3.
        $this->assertSame(3, $this->ballastInto(2, $driver));
    }

    public function test_a_mid_season_joiner_starts_from_the_starting_ballast_of_their_first_round(): void
    {
        $this->cumulative(['success_ballast_starting' => '2, 3, 4, 5, 6, 7, 8, 9, 10, 11']);
        [$regular, $joiner] = $this->drivers(2);
        $field = $this->drivers(4);

        // Everyone starts with round 1's 2 kg; a joiner who hasn't raced yet gets
        // the value of the round they're about to race, not their sign-up round.
        $this->assertSame(2, $this->ballastInto(1, $regular));
        $this->assertSame(4, $this->ballastInto(3, $joiner));

        $this->finishRound(1, [...$field, $regular]);           // P5: 2 + 0
        $this->finishRound(2, [...$field, $regular]);           // 2 + 0
        $this->finishRound(3, [$joiner, ...$field, $regular]);  // joiner P1: 4 + 5

        $this->assertSame(9, $this->ballastInto(4, $joiner));
        $this->assertSame(1, $this->ballastInto(4, $regular)); // P6 in round 3: 2 - 1

        $first = app(EntryBalanceService::class)->history($this->championship)[$joiner->id][0];
        $this->assertSame(['round' => 3, 'before' => 4, 'position' => 1, 'delta' => 5, 'after' => 9], array_intersect_key($first, array_flip(['round', 'before', 'position', 'delta', 'after'])));

        // A round past the end of the table keeps its last value.
        $this->assertSame(11, $this->ballastInto(14, $this->driver()));
    }

    public function test_changing_a_rounds_results_recalculates_that_round_and_every_later_one(): void
    {
        $this->cumulative();
        [$a, $b] = $this->drivers(2);

        $round1 = $this->makeRound(1, 'finished');
        $aResult = $this->finish($round1, $a, 1);
        $bResult = $this->finish($round1, $b, 2);
        $this->finishRound(2, [$a, $b]);

        $this->assertSame(10, $this->ballastInto(3, $a));

        // A steward swaps round 1's order.
        $aResult->update(['position' => 2]);
        $bResult->update(['position' => 1]);

        $this->assertSame(8, $this->ballastInto(3, $a));
        $this->assertSame(8, $this->ballastInto(3, $b));
    }

    public function test_the_wizard_saves_and_validates_the_success_ballast_settings(): void
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->championship->league_id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        $url = route('admin.leagues.championships.wizard.update', [$this->championship->league_id, $this->championship, 'penalties']);

        $this->actingAs($manager->refresh())
            ->put($url, ['settings' => ['balance' => [
                'success_ballast_enabled' => 1, 'success_ballast_kg' => '5, 41', 'success_ballast_cap' => 50,
                'success_ballast_starting' => '-45',
            ]]])
            ->assertSessionHasErrors(['settings.balance.success_ballast_kg', 'settings.balance.success_ballast_cap', 'settings.balance.success_ballast_starting']);

        $this->actingAs($manager)
            ->put($url, ['settings' => ['penalties' => ['affects' => 'none'], 'balance' => [
                'success_ballast_enabled' => 1, 'success_ballast_mode' => 'cumulative',
                'success_ballast_kg' => '40, 3, -1, -40', 'success_ballast_cap' => 30,
                'success_ballast_min_laps' => 3,
                'success_ballast_starting' => '2, 3, 4',
            ]]])
            ->assertSessionHasNoErrors();

        $balance = $this->championship->fresh()->settings->balance;
        $this->assertTrue($balance->success_ballast_enabled);
        $this->assertSame('cumulative', $balance->success_ballast_mode);
        $this->assertSame('40, 3, -1, -40', $balance->success_ballast_kg);
        $this->assertEquals(30, $balance->success_ballast_cap);
        $this->assertEquals(3, $balance->success_ballast_min_laps);
        $this->assertSame('2, 3, 4', $balance->success_ballast_starting);
    }

    public function test_an_existing_championship_defaults_to_next_round_only(): void
    {
        $settings = $this->championship->settings->toArray();
        unset($settings['balance']['success_ballast_mode'], $settings['balance']['success_ballast_cap']);
        $this->championship->settings = $settings;
        $this->championship->save();

        $balance = $this->championship->fresh()->settings->balance;
        $this->assertSame('next_round', $balance->success_ballast_mode);
        $this->assertSame(40, $balance->success_ballast_cap);
    }

    public function test_the_championship_page_draws_the_ballast_record(): void
    {
        $this->cumulative(['success_ballast_starting' => '2']);
        $this->championship->update(['status' => 'active']);
        [$winner, $second] = $this->drivers(2);
        $this->finishRound(1, [$winner, $second]);

        $chart = app(EntryBalanceService::class)->chart($this->championship);
        $this->assertSame(1, $chart['rounds']);
        $this->assertSame([[0, 2], [1, 7]], $chart['series'][0]['points']);

        $this->get(route('championships.show', $this->championship))
            ->assertOk()
            ->assertSee('Ballast Record')
            ->assertSee($winner->displayName().' — R1: 7 kg');
    }

    public function test_disabled_success_ballast_adds_nothing(): void
    {
        $this->setBalance(['success_ballast_enabled' => false]);
        $this->finishRound(1, [$this->driver()]);

        $this->assertSame([], app(EntryBalanceService::class)->successBallast($this->makeRound(2)));
    }
}
