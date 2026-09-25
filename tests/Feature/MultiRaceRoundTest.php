<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\PointsScheme;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\AccResultImportService;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// League feedback (NLRL, 2026-09): a round can run several races in one server
// event (P, Q, R, R…). Each race is imported, rated and scored on its own, and the
// round's standings entry adds its races up.
class MultiRaceRoundTest extends TestCase
{
    use RefreshDatabase;

    private function makeRound(array $overrides = []): Race
    {
        return Race::create(array_merge([
            'title' => 'Double Header', 'track' => 'monza', 'game' => 'acc', 'status' => 'closed',
            'scheduled_at' => now()->subHours(2), 'practice_duration' => 10, 'qualifying_duration' => 15,
            'race_duration' => 25, 'race_durations' => [25, 20],
        ], $overrides));
    }

    /** One R session's results file: [platformId => finishing order]. */
    private function raceSession(array $order, int $sessionIndex): string
    {
        $lines = [];
        foreach ($order as $i => $platformId) {
            $lines[] = [
                'car' => ['raceNumber' => 10 + $i, 'carModel' => 25, 'drivers' => [['playerId' => $platformId, 'firstName' => '', 'lastName' => 'Driver '.$i]]],
                'timing' => ['bestLap' => 95000 + $i, 'lapCount' => 10, 'totalTime' => 960000 + $i * 1000],
            ];
        }

        return json_encode([
            'sessionType' => 'R', 'sessionIndex' => $sessionIndex,
            'sessionResult' => ['bestlap' => 95000, 'leaderBoardLines' => $lines],
        ]);
    }

    private function drivers(Race $race, int $count): array
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $users[] = $user = User::factory()->create(['platform' => 'steam', 'platform_id' => 'S7656119800000000'.$i]);
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        return $users;
    }

    public function test_the_server_event_runs_one_race_session_per_race(): void
    {
        $sessions = app(AccServerConfigService::class)->configuration($this->makeRound())['sessions'];

        $this->assertSame(['P', 'Q', 'R', 'R'], array_column($sessions, 'sessionType'));
        $this->assertSame([10, 15, 25, 20], array_column($sessions, 'sessionDurationMinutes'));

        $single = app(AccServerConfigService::class)->configuration($this->makeRound(['race_durations' => null]))['sessions'];
        $this->assertSame(['P', 'Q', 'R'], array_column($single, 'sessionType'));
    }

    public function test_each_race_imports_separately_and_the_round_finishes_after_the_last(): void
    {
        Storage::fake('local');
        $race = $this->makeRound();
        [$a, $b] = $this->drivers($race, 2);
        $importer = new AccResultImportService;

        // Race 1 (sessionIndex 2, after P and Q): A wins.
        $importer->processSessions($this->raceSession([$a->platform_id, $b->platform_id], 2), $race, 'r1.json');
        $this->assertNotSame('finished', $race->fresh()->status);

        // Race 2 (sessionIndex 3): B wins — doesn't overwrite race 1.
        $importer->processSessions($this->raceSession([$b->platform_id, $a->platform_id], 3), $race, 'r2.json');
        $this->assertSame('finished', $race->fresh()->status);

        $this->assertSame(1, RaceResult::where(['race_id' => $race->id, 'race_number' => 1, 'user_id' => $a->id])->value('position'));
        $this->assertSame(1, RaceResult::where(['race_id' => $race->id, 'race_number' => 2, 'user_id' => $b->id])->value('position'));
        $this->assertSame(4, RaceResult::where('race_id', $race->id)->where('session_type', 'race')->count());

        Storage::disk('local')->assertExists('race-results/'.$race->id.'.json');
        Storage::disk('local')->assertExists('race-results/'.$race->id.'-race2.json');

        // Re-importing race 2's file is idempotent.
        $importer->processSessions($this->raceSession([$b->platform_id, $a->platform_id], 3), $race, 'r2.json');
        $this->assertSame(4, RaceResult::where('race_id', $race->id)->where('session_type', 'race')->count());
    }

    public function test_standings_add_up_every_race_of_a_round(): void
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $scheme = PointsScheme::withoutTenantScope()->create([
            'league_id' => $league->id, 'name' => 'Simple', 'type' => 'manual',
            'points_table' => [1 => 25, 2 => 18], 'fastest_lap_points' => 0, 'pole_points' => 0, 'leading_lap_points' => 0,
        ]);
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'active', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['scoring' => ['points_scheme_id' => $scheme->id]]);
        $championship->save();

        $race = $this->makeRound(['championship_id' => $championship->id, 'round_number' => 1, 'status' => 'finished']);
        [$a, $b] = $this->drivers($race, 2);
        foreach ([[1, $a, 1], [1, $b, 2], [2, $b, 1], [2, $a, 2]] as [$raceNumber, $user, $position]) {
            RaceResult::create([
                'race_id' => $race->id, 'session_type' => 'race', 'race_number' => $raceNumber, 'user_id' => $user->id,
                'player_id' => $user->platform_id, 'position' => $position, 'lap_count' => 10, 'total_time' => 960000,
                'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false, 'fastest_lap' => false,
            ]);
        }

        $standings = collect($championship->fresh()->computeStandings())->keyBy('user_id');

        $this->assertEquals(43, $standings[$a->id]['total_points']);
        $this->assertEquals(43, $standings[$b->id]['total_points']);
        $this->assertCount(1, $standings[$a->id]['rounds']);
        $this->assertEquals([1 => 25, 2 => 18], collect($standings[$a->id]['rounds'][0]['races'])->map->points->all());
    }

    public function test_results_page_shows_one_race_at_a_time(): void
    {
        $race = $this->makeRound(['status' => 'finished']);
        [$a] = $this->drivers($race, 1);
        foreach ([1, 2] as $raceNumber) {
            RaceResult::create([
                'race_id' => $race->id, 'session_type' => 'race', 'race_number' => $raceNumber, 'user_id' => $a->id,
                'player_id' => $a->platform_id, 'driver_name' => 'Race'.$raceNumber.'Winner', 'position' => 1,
                'lap_count' => 10, 'total_time' => 960000, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
            ]);
        }

        $this->get(route('results.index', ['race' => $race->id, 'race_number' => 2]))
            ->assertOk()
            ->assertSee('Race 2')
            ->assertViewHas('raceNumber', 2)
            ->assertViewHas('raceResults', fn ($results) => $results->count() === 1 && $results->first()->race_number === 2);
    }
}
