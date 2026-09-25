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

// League feedback (NLRL, 2026-09): success ballast — a round's finishing positions
// earn ballast for the next round only, pushed as the entrylist's ballastKg together
// with the championship's manual per-driver/team adjustments.
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
        $this->setBalance(['success_ballast_enabled' => true, 'success_ballast_kg' => '30, 20, 10']);
    }

    private function setBalance(array $balance): void
    {
        $this->championship->settings = array_replace_recursive($this->championship->settings->toArray(), ['balance' => $balance]);
        $this->championship->save();
    }

    private function makeRound(int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $this->championship->id, 'round_number' => $number,
            'title' => 'Round '.$number, 'track' => 'monza', 'game' => 'acc',
            'status' => $status, 'scheduled_at' => now()->addWeeks($number),
        ]);
    }

    private function finish(Race $race, User $user, int $position, array $extra = []): void
    {
        RaceResult::create(array_merge([
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id, 'player_id' => $user->platform_id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20, 'total_time' => 1_200_000 + $position,
            'car_number' => $position, 'car_class' => 'GT3',
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ], $extra));
    }

    private function driver(): User
    {
        return User::factory()->create(['platform' => 'xbox', 'platform_id' => 'M'.fake()->unique()->numerify('################')]);
    }

    /** ballastKg per driver name on a round's entrylist. */
    private function ballastOnEntryList(Race $race): array
    {
        return collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->mapWithKeys(fn ($entry) => [$entry['drivers'][0]['lastName'] => $entry['ballastKg']])
            ->all();
    }

    public function test_previous_round_positions_earn_ballast_for_the_next_round_only(): void
    {
        [$first, $second, $third, $fourth, $retired] = [$this->driver(), $this->driver(), $this->driver(), $this->driver(), $this->driver()];
        foreach ([$first, $second, $third, $fourth, $retired] as $user) {
            $user->update(['team' => null]);
        }

        $round1 = $this->makeRound(1, 'finished');
        $this->finish($round1, $retired, 1, ['dnf' => true]);
        $this->finish($round1, $first, 2);
        $this->finish($round1, $second, 3);
        $this->finish($round1, $third, 4);
        $this->finish($round1, $fourth, 5);

        $round2 = $this->makeRound(2);
        foreach ([$first, $second, $third, $fourth, $retired] as $user) {
            RaceRegistration::create(['race_id' => $round2->id, 'user_id' => $user->id]);
        }

        $this->assertSame([
            $first->name => 30, $second->name => 20, $third->name => 10, $fourth->name => 0, $retired->name => 0,
        ], $this->ballastOnEntryList($round2));

        // Round 1 itself carries nothing; round 3 only looks at round 2 (no build-up).
        RaceRegistration::create(['race_id' => $round1->id, 'user_id' => $first->id]);
        $this->assertSame([$first->name => 0], $this->ballastOnEntryList($round1));

        $round2->update(['status' => 'finished']);
        $this->finish($round2, $fourth, 1);
        $round3 = $this->makeRound(3);
        RaceRegistration::create(['race_id' => $round3->id, 'user_id' => $first->id]);
        RaceRegistration::create(['race_id' => $round3->id, 'user_id' => $fourth->id]);

        $this->assertSame([$first->name => 0, $fourth->name => 30], $this->ballastOnEntryList($round3));
    }

    public function test_multiclass_ranks_within_each_class_and_a_team_car_shares_its_ballast(): void
    {
        $this->championship->update(['is_multiclass' => true]);
        [$gt3Winner, $gt3Second, $gt4WinnerA, $gt4WinnerB] = [$this->driver(), $this->driver(), $this->driver(), $this->driver()];

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
        $winner->update(['team' => null]);
        $this->setBalance(['adjustments' => [
            ['scope' => 'driver', 'target' => $winner->displayName(), 'ballast_kg' => 90, 'restrictor_percent' => 25],
        ]]);

        $round1 = $this->makeRound(1, 'finished');
        $this->finish($round1, $winner, 1);
        $round2 = $this->makeRound(2);
        RaceRegistration::create(['race_id' => $round2->id, 'user_id' => $winner->id]);

        $entry = app(AccServerConfigService::class)->entryList($round2)['entries'][0];
        $this->assertSame(100, $entry['ballastKg']);
        $this->assertSame(20, $entry['restrictor']);
    }

    public function test_the_wizard_saves_the_success_ballast_settings(): void
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->championship->league_id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        $url = route('admin.leagues.championships.wizard.update', [$this->championship->league_id, $this->championship, 'penalties']);

        $this->actingAs($manager->refresh())
            ->put($url, ['settings' => ['balance' => ['success_ballast_enabled' => 1, 'success_ballast_kg' => 'lots']]])
            ->assertSessionHasErrors('settings.balance.success_ballast_kg');

        $this->actingAs($manager)
            ->put($url, ['settings' => ['penalties' => ['affects' => 'none'], 'balance' => ['success_ballast_enabled' => 1, 'success_ballast_kg' => '25, 15']]])
            ->assertSessionHasNoErrors();

        $balance = $this->championship->fresh()->settings->balance;
        $this->assertTrue($balance->success_ballast_enabled);
        $this->assertSame('25, 15', $balance->success_ballast_kg);
    }

    public function test_disabled_success_ballast_adds_nothing(): void
    {
        $this->setBalance(['success_ballast_enabled' => false]);
        $winner = $this->driver();

        $round1 = $this->makeRound(1, 'finished');
        $this->finish($round1, $winner, 1);

        $this->assertSame([], app(EntryBalanceService::class)->successBallast($this->makeRound(2)));
    }
}
