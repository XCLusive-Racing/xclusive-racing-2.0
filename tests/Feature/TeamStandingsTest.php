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
            'format'  => ['driver_swaps_enabled' => true],
        ]);
        $championship->save();

        return $championship;
    }

    private function finishedRound(Championship $championship, int $roundNumber): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $roundNumber,
            'title' => 'Round ' . $roundNumber, 'track' => 'Monza', 'game' => 'acc',
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

    public function test_a_teams_points_combine_every_member_who_scored_across_rounds(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['points_scheme_id' => null],
        ]);
        // Legacy flat points_system so a finish actually scores something.
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $team   = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
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

    public function test_team_standings_are_empty_when_the_setting_is_off(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, teamPointsEnabled: false);

        $owner = User::factory()->create();
        $team  = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id]);

        $this->assertSame([], $championship->computeTeamStandings());
    }

    public function test_team_standings_are_empty_when_nobody_registered_as_a_team(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        $this->assertSame([], $championship->computeTeamStandings());
    }

    public function test_public_show_page_renders_team_standings(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $owner = User::factory()->create();
        $team  = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id]);
        $r1 = $this->finishedRound($championship, 1);
        $this->makeResult($r1, $owner, 1);

        $this->get(route('championships.show', $championship->id))
            ->assertOk()
            ->assertSee('Team Standings')
            ->assertSee('Apex Racing');
    }
}
