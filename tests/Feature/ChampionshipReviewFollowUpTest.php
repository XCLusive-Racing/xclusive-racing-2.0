<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Championship review 2026-10-25, items 5-9: who a team championship's standings
// list, a season that never ended, round titles after a rename, publishing an
// empty championship, and ties on points.
class ChampionshipReviewFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function championship(array $attributes = []): Championship
    {
        return Championship::create($attributes + [
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'slug' => 'test-cup', 'game' => 'acc', 'platform' => 'console',
            'season' => 2026, 'status' => 'registration_open', 'registration_open' => true,
            'settings' => ChampionshipSettingsSchema::defaults(), 'points_system' => [25, 18, 15, 12],
        ]);
    }

    private function round(Championship $championship, int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number, 'title' => 'Round '.$number,
            'track' => 'Monza', 'game' => 'acc', 'status' => $status, 'scheduled_at' => now()->addWeeks($number),
        ]);
    }

    private function finish(Race $race, User $user, int $position): void
    {
        RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'race_number' => 1, 'user_id' => $user->id, 'driver_name' => $user->name,
            'position' => $position, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    // --- 5 ---

    public function test_a_team_championships_standings_list_the_cars_drivers_not_the_manager(): void
    {
        $championship = $this->championship();
        $manager = User::factory()->create();
        [$d1, $d2] = User::factory()->count(2)->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $manager->id]);
        $team->members()->attach([$d1->id, $d2->id]);
        ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $manager->id, 'racing_team_id' => $team->id,
            'driver_ids' => [$d1->id, $d2->id], 'car_number' => 7,
        ]);

        $ids = collect($championship->computeStandings())->pluck('user_id')->sort()->values()->all();

        $this->assertSame(collect([$d1->id, $d2->id])->sort()->values()->all(), $ids);
    }

    // --- 6 ---

    public function test_a_championship_completes_once_every_round_finished_and_a_new_round_reopens_it(): void
    {
        $championship = $this->championship();
        $this->round($championship, 1, 'finished');
        $open = $this->round($championship, 2);

        Championship::completeFinished();
        $this->assertSame('registration_open', $championship->fresh()->status);

        $open->update(['status' => 'finished']);
        $this->assertSame(1, Championship::completeFinished());

        $championship->refresh();
        $this->assertSame('completed', $championship->status);
        $this->assertSame('Completed', $championship->statusLabel());
        $this->assertFalse($championship->acceptsRegistrations());

        $manager = User::factory()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        $this->actingAs($manager->refresh())->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
            'track' => 'Spa', 'scheduled_at' => now()->addMonth()->startOfHour()->tz('Europe/London')->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertSame('registration_closed', $championship->fresh()->status);
    }

    // --- 7 ---

    public function test_renaming_retitles_the_rounds(): void
    {
        $championship = $this->championship(['name' => 'Thursday Night']);
        $this->round($championship, 3);

        $championship->update(['name' => 'Monday Night']);
        $championship->retitleRounds();

        $this->assertSame(['Monday Night — Round 3'], $championship->rounds()->pluck('title')->all());
    }

    // --- 8 ---

    public function test_publishing_needs_a_round_and_a_points_system(): void
    {
        $championship = $this->championship(['status' => 'draft', 'points_system' => null]);
        $manager = User::factory()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->assertSame(['Add at least one round.', 'Pick a points scheme on the Scoring step.'], $championship->publishBlockers());

        $this->actingAs($manager->refresh())
            ->post(route('admin.leagues.championships.publish', [$this->league, $championship]))
            ->assertSessionHasErrors('publish');
        $this->assertSame('draft', $championship->fresh()->status);

        $this->round($championship, 1);
        $championship->update(['points_system' => [25, 18]]);
        $this->assertSame([], $championship->publishBlockers());
        $this->assertSame(['1 round has no server yet, so it won\'t be pushed or run.'], $championship->publishWarnings());
    }

    // --- 9 ---

    public function test_a_tie_on_points_goes_to_the_better_finishes(): void
    {
        $championship = $this->championship(['points_system' => [10, 6, 4, 2]]);
        [$winner, $steady] = User::factory()->count(2)->create();
        foreach ([$winner, $steady] as $driver) {
            ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);
        }
        $others = User::factory()->count(2)->create();

        // Both on 12: a win and a fourth (10 + 2) against two second places (6 + 6).
        $r1 = $this->round($championship, 1, 'finished');
        $this->finish($r1, $steady, 2);
        $this->finish($r1, $others[0], 1);
        $r2 = $this->round($championship, 2, 'finished');
        $this->finish($r2, $steady, 2);
        $this->finish($r2, $others[1], 1);
        $r3 = $this->round($championship, 3, 'finished');
        $this->finish($r3, $winner, 1);
        $this->finish($r3, $others[0], 3);
        $r4 = $this->round($championship, 4, 'finished');
        $this->finish($r4, $others[1], 1);
        $this->finish($r4, $others[0], 2);
        $this->finish($r4, $winner, 4);

        $standings = collect($championship->fresh()->computeStandings())->keyBy('user_id');
        $this->assertEquals(12, $standings[$winner->id]['total_points']);
        $this->assertEquals(12, $standings[$steady->id]['total_points']);

        $order = collect($championship->fresh()->computeStandings())->pluck('user_id');
        $this->assertTrue($order->search($winner->id) < $order->search($steady->id));
    }
}
