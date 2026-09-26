<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request: settings.scoring.team_points_enabled existed since the
// points-scheme rebuild but never computed a separate team classification anywhere.
class TeamStandingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(League $league, bool $teamPointsEnabled = true): Championship
    {
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['team_points_enabled' => $teamPointsEnabled],
            'format' => ['driver_swaps_enabled' => true],
        ]);
        $championship->save();

        return $championship;
    }

    private function finishedRound(Championship $championship, int $roundNumber): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $roundNumber,
            'title' => 'Round '.$roundNumber, 'track' => 'Monza', 'game' => 'acc',
            'status' => 'finished', 'scheduled_at' => now()->subWeek(),
        ]);
    }

    private function makeResult(Race $race, User $user, int $position): RaceResult
    {
        return RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    public function test_a_cars_points_combine_every_driver_who_scored_across_rounds(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['points_scheme_id' => null],
        ]);
        // Legacy flat points_system so a finish actually scores something.
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach($member->id);

        ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id,
        ]);

        $r1 = $this->finishedRound($championship, 1);
        $r2 = $this->finishedRound($championship, 2);
        // Different drivers from the same team score in different rounds — a
        // realistic driver-swap scenario. Round 1: owner drives, P1 = 25.
        $this->makeResult($r1, $owner, 1);
        // Round 2: the other member drives, P2 = 18.
        $this->makeResult($r2, $member, 2);

        $teamStandings = $championship->computeTeamStandings();

        $this->assertCount(1, $teamStandings);
        $this->assertSame('Apex Racing', $teamStandings[0]['team']->name);
        $this->assertSame(43.0, (float) $teamStandings[0]['total_points']);
    }

    // Both drivers of a car get the car's result row — the car still scores that
    // finish once, and a second car of the same team is its own row.
    public function test_each_car_scores_its_own_finish_once(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $owner = User::factory()->create();
        [$a, $b, $c, $d] = User::factory()->count(4)->create()->all();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach([$a->id, $b->id, $c->id, $d->id]);

        foreach ([[7, [$a->id, $b->id]], [8, [$c->id, $d->id]]] as [$number, $driverIds]) {
            ChampionshipRegistration::create([
                'championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id,
                'car_number' => $number, 'driver_ids' => $driverIds,
            ]);
        }

        $r1 = $this->finishedRound($championship, 1);
        $this->makeResult($r1, $a, 1);
        $this->makeResult($r1, $b, 1);
        $this->makeResult($r1, $c, 2);
        $this->makeResult($r1, $d, 2);

        $points = collect($championship->computeTeamStandings())->pluck('total_points', 'car_label')->map(fn ($p) => (float) $p);

        $this->assertEquals(['#7' => 25.0, '#8' => 18.0], $points->all());
    }

    // settings.scoring.team_scoring_cars: each round only a team's best N cars
    // score for the team; every car still keeps all its own points.
    public function test_team_championship_counts_each_teams_best_cars_per_round(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['team_scoring_cars' => 2],
        ]);
        $championship->points_system = [25, 18, 15, 12];
        $championship->save();

        $owners = User::factory()->count(2)->create();
        $apex = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owners[0]->id]);
        $bolt = RacingTeam::create(['name' => 'Bolt Motorsport', 'tag' => 'BLT', 'owner_id' => $owners[1]->id]);

        [$a1, $a2, $a3, $b1] = User::factory()->count(4)->create()->all();
        foreach ([[$apex, $a1, 1], [$apex, $a2, 2], [$apex, $a3, 3], [$bolt, $b1, 4]] as [$team, $driver, $number]) {
            $team->members()->attach($driver->id);
            ChampionshipRegistration::create([
                'championship_id' => $championship->id, 'user_id' => $team->owner_id, 'racing_team_id' => $team->id,
                'car_number' => $number, 'driver_ids' => [$driver->id],
            ]);
        }

        // Round 1: Apex 1-2-3, Bolt 4th → Apex 25 + 18, Bolt 12.
        $r1 = $this->finishedRound($championship, 1);
        foreach ([$a1, $a2, $a3, $b1] as $i => $driver) {
            $this->makeResult($r1, $driver, $i + 1);
        }
        // Round 2: a different Apex car wins → Apex 25 + 15, Bolt 18.
        $r2 = $this->finishedRound($championship, 2);
        foreach ([$a3, $b1, $a1, $a2] as $i => $driver) {
            $this->makeResult($r2, $driver, $i + 1);
        }

        $teams = collect($championship->computeTeamChampionship())->mapWithKeys(fn ($row) => [$row['team']->name => (float) $row['total_points']]);
        $this->assertEquals(['Apex Racing' => 83.0, 'Bolt Motorsport' => 30.0], $teams->all());

        // Every car still scores in full for itself.
        $cars = collect($championship->computeTeamStandings())->pluck('total_points', 'car_label')->map(fn ($p) => (float) $p);
        $this->assertEquals(['#1' => 40.0, '#2' => 30.0, '#3' => 40.0, '#4' => 30.0], $cars->sortKeys()->all());

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Team Championship')
            ->assertSee('Car Standings');
    }

    public function test_no_team_championship_without_a_scoring_cars_limit(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $this->assertSame([], $championship->computeTeamChampionship());
    }

    public function test_team_standings_are_empty_when_the_setting_is_off(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, teamPointsEnabled: false);

        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id]);

        $this->assertSame([], $championship->computeTeamStandings());
    }

    public function test_team_standings_are_empty_when_nobody_registered_as_a_team(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        $this->assertSame([], $championship->computeTeamStandings());
    }

    public function test_public_show_page_renders_team_standings(): void
    {
        $league = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id]);
        $r1 = $this->finishedRound($championship, 1);
        $this->makeResult($r1, $owner, 1);

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Team Standings')
            ->assertSee('Apex Racing');
    }
}
