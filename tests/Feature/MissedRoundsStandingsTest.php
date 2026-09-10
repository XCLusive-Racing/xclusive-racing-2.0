<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request: max_missed_rounds/missed_rounds_action/missed_rounds_penalty_points
// existed as columns (native) and, now, settings (league) but were never wired into
// Championship::buildDriverStandings() for either. Also covers the registration-seeding
// fix these needed: a driver with zero results didn't appear in standings at all before,
// so there was nobody to apply a missed-rounds penalty to.
class MissedRoundsStandingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    private function finishedRound(Championship $championship, int $roundNumber): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $roundNumber,
            'title' => 'Round ' . $roundNumber, 'track' => 'Monza', 'game' => 'acc',
            'status' => 'finished', 'scheduled_at' => now()->subWeeks(4 - $roundNumber),
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

    public function test_a_registered_driver_with_zero_results_still_appears_in_standings(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $driver       = User::factory()->create();

        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        $standings = $championship->computeStandings();

        $this->assertCount(1, $standings);
        $this->assertSame(0.0, (float) $standings[0]['total_points']);
    }

    public function test_a_spectator_registration_never_appears_in_standings(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $spectator    = User::factory()->create();

        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $spectator->id, 'is_spectator' => true]);

        $this->assertCount(0, $championship->computeStandings());
    }

    public function test_missing_more_rounds_than_allowed_deducts_the_configured_points(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['max_missed_rounds' => 1, 'missed_rounds_action' => 'penalise', 'missed_rounds_penalty_points' => 5],
        ]);
        $championship->save();

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        // 4 rounds run, driver only shows up for round 1 — misses 3, allowed 1,
        // so 2 rounds over the limit × 5 points = 10 point penalty.
        $r1 = $this->finishedRound($championship, 1);
        $this->finishedRound($championship, 2);
        $this->finishedRound($championship, 3);
        $this->finishedRound($championship, 4);
        $this->makeResult($r1, $driver, 1);

        $standings = $championship->computeStandings();

        // No points scheme/legacy points_system set on this championship, so the
        // P1 finish itself is worth 0 — total is purely the missed-rounds penalty.
        $this->assertSame(10, $standings[0]['missed_rounds_penalty']);
        $this->assertSame(-10.0, (float) $standings[0]['total_points']);
    }

    public function test_missing_fewer_rounds_than_allowed_is_not_penalised(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['max_missed_rounds' => 2, 'missed_rounds_action' => 'penalise', 'missed_rounds_penalty_points' => 5],
        ]);
        $championship->save();

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);

        $r1 = $this->finishedRound($championship, 1);
        $this->finishedRound($championship, 2); // missed, but only 1 missed <= 2 allowed
        $this->makeResult($r1, $driver, 1);

        $standings = $championship->computeStandings();

        $this->assertSame(0, $standings[0]['missed_rounds_penalty']);
    }

    public function test_missed_rounds_action_none_never_penalises(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['max_missed_rounds' => 0, 'missed_rounds_action' => 'none', 'missed_rounds_penalty_points' => 5],
        ]);
        $championship->save();

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);
        $this->finishedRound($championship, 1);
        $this->finishedRound($championship, 2);

        $standings = $championship->computeStandings();

        $this->assertSame(0, $standings[0]['missed_rounds_penalty']);
    }

    public function test_native_championship_reads_missed_rounds_rule_from_flat_columns(): void
    {
        $xcl = League::system();
        $championship = $this->makeChampionship($xcl, [
            'max_missed_rounds' => 0, 'missed_rounds_action' => 'penalise', 'missed_rounds_penalty_points' => 3,
        ]);

        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);
        $this->finishedRound($championship, 1); // missed, 0 allowed

        $standings = $championship->computeStandings();

        $this->assertSame(-3.0, (float) $standings[0]['total_points']);
    }
}
