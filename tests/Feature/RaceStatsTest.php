<?php

namespace Tests\Feature;

use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\Membership;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RaceSessionFile;
use App\Models\User;
use App\Services\FtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-06: the detailed race stats (best laps, sectors, consistency, lap
// by lap, penalties) "never came through" — the raw results file they're built from sat on
// the server's local disk, wiped on every deploy. It's kept in the database now, and the
// stats are a membership perk (Supporter and up).
class RaceStatsTest extends TestCase
{
    use RefreshDatabase;

    private function resultsFile(): string
    {
        return json_encode(['sessionType' => 'R', 'sessionResult' => ['bestlap' => 95000, 'leaderBoardLines' => [[
            'car' => ['raceNumber' => 7, 'carModel' => 25, 'drivers' => [['playerId' => 'S1', 'firstName' => 'Test', 'lastName' => 'Driver']]],
            'timing' => ['bestLap' => 95000, 'lapCount' => 10, 'totalTime' => 960000],
        ]]]]);
    }

    private function finishedRace(): Race
    {
        $race = Race::create(['title' => 'Daily Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDay()]);
        RaceResult::create([
            'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => 'Monza', 'race_game' => 'acc',
            'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
            'player_id' => 'S1', 'driver_name' => 'Test Driver', 'car_number' => 7, 'position' => 1, 'lap_count' => 10,
        ]);

        return $race;
    }

    public function test_supporters_see_the_stats_and_everyone_else_a_locked_preview(): void
    {
        $race = $this->finishedRace();
        RaceSessionFile::put($race, 1, $this->resultsFile());
        $url = route('results.index', ['race' => $race->id]);

        $this->get($url)->assertOk()->assertSee('Log in to see the stats')->assertDontSee('data-tab-btn="stats-consistency"', false);

        $driver = User::factory()->create();
        $this->actingAs($driver)->get($url)->assertSee('Unlock with a membership')->assertDontSee('data-tab-btn="stats-consistency"', false);

        Membership::create(['user_id' => $driver->id, 'plan' => 'monthly', 'status' => 'active', 'mode' => 'test', 'paid_until' => now()->addMonth()]);
        $this->actingAs($driver->fresh())->get($url)->assertSee('data-tab-btn="stats-consistency"', false)->assertDontSee('Unlock with a membership');
    }

    public function test_a_race_without_stats_shows_no_preview(): void
    {
        $race = $this->finishedRace();

        $this->get(route('results.index', ['race' => $race->id]))->assertOk()->assertDontSee('Detailed race stats');
    }

    public function test_restore_refetches_the_file_from_gportal_without_touching_results(): void
    {
        $race = $this->finishedRace();
        $server = FtpServer::withoutGlobalScopes()->forceCreate([
            'name' => 'GP 1', 'host' => 'ftp.example', 'port' => 21, 'username' => 'u', 'password' => 'p', 'path' => '/results',
        ]);
        FtpImportedFile::create(['ftp_server_id' => $server->id, 'race_id' => $race->id, 'filename' => '261004_1800_R.json']);

        $this->mock(FtpService::class, function ($mock) {
            $mock->shouldReceive('connect')->andReturn(true);
            $mock->shouldReceive('getFileContent')->with('/results/261004_1800_R.json')->andReturn($this->resultsFile());
            $mock->shouldReceive('disconnect');
        });

        $this->artisan('results:restore-stats')->assertSuccessful();

        $this->assertSame($this->resultsFile(), RaceSessionFile::jsonFor($race));
        $this->assertSame(1, RaceResult::where('race_id', $race->id)->count());
        $this->assertNull(RaceResult::where('race_id', $race->id)->value('elo_change'));
    }
}
