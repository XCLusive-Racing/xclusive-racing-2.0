<?php

namespace Tests\Feature;

use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\FtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

// User-directed 2026-10: "iemand zei dat de quali niet door kwam" (Sunday 2026-10-18).
//
// Race 725 (Daily Race, 20:00 UTC) wrote its quali file at 22:25 Berlin = 20:25 UTC. The
// importer only treated a race as a candidate 30 minutes past scheduled_at, so 725 wasn't
// one yet and the file matched race 723 (18:00, same server, still unfinished). A driver
// registered for both got a quali row on 723, the file was marked imported, and 725 never
// got its quali.
class ImportGportalResultsMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_quali_file_written_before_the_30_minute_mark_goes_to_its_own_race(): void
    {
        $this->travelTo(Carbon::parse('2026-10-18 20:27:00', 'UTC'));

        $server = FtpServer::create([
            'name' => 'Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'server_number' => 3,
            'league_id' => League::system()->id, 'game' => 'acc', 'platform' => 'ps5',
        ]);

        $user = User::factory()->create(['platform_id' => 'P123']);

        $earlier = $this->makeRace($server, '2026-10-18 16:00:00');
        $daily = $this->makeRace($server, '2026-10-18 20:00:00');
        foreach ([$earlier, $daily] as $race) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        $filename = '261018_222548_Q.json';
        $this->mock(FtpService::class, function ($mock) use ($filename) {
            $mock->shouldReceive('connect')->andReturnTrue();
            $mock->shouldReceive('disconnect');
            $mock->shouldReceive('listFiles')->andReturn(['json' => [['name' => $filename]], 'all' => []]);
            $mock->shouldReceive('getFileContent')->andReturn(json_encode([
                'sessionType' => 'Q',
                'sessionResult' => [
                    'bestlap' => 105000,
                    'leaderBoardLines' => [[
                        'car' => ['raceNumber' => 7, 'carModel' => 25, 'drivers' => [['playerId' => 'P123', 'firstName' => 'Jan', 'lastName' => 'Jansen']]],
                        'timing' => ['bestLap' => 105000, 'lapCount' => 5, 'totalTime' => 600000],
                    ]],
                ],
            ]));
        });

        $this->artisan('gportal:import-results')->assertSuccessful();

        $this->assertSame(1, RaceResult::where('race_id', $daily->id)->where('session_type', 'quali')->count());
        $this->assertSame(0, RaceResult::where('race_id', $earlier->id)->count());
        $this->assertSame($daily->id, FtpImportedFile::where('filename', $filename)->value('race_id'));
    }

    private function makeRace(FtpServer $server, string $at): Race
    {
        return Race::create([
            'title' => 'Race '.$at, 'track' => 'Monza', 'game' => 'acc', 'status' => 'closed',
            'scheduled_at' => $at, 'slot_time' => $at, 'ftp_server_id' => $server->id,
            'practice_duration' => 10, 'qualifying_duration' => 12, 'race_duration' => 30,
        ]);
    }
}
