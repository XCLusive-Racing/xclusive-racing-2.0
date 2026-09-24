<?php

namespace Tests\Feature;

use App\Models\Bop;
use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\Race;
use App\Models\Role;
use App\Models\User;
use App\Services\AccResultImportService;
use App\Services\AccServerConfigService;
use App\Services\FtpService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// ACC PC and ACC Console builds can't share a server, so an event can only be put on a
// server of its own platform, and the server announces the right platform in its name.
class AccPcServerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function makeServer(string $platform, ?int $leagueId = null): FtpServer
    {
        return FtpServer::create([
            'name' => ucfirst($platform).' Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'server_number' => 3,
            'league_id' => $leagueId ?? League::system()->id, 'game' => 'acc', 'platform' => $platform,
        ]);
    }

    private function makeRace(string $game): Race
    {
        return Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => $game,
            'status' => 'open', 'scheduled_at' => now()->addWeek()->startOfHour(), 'race_duration' => 30,
        ]);
    }

    public function test_server_platform_must_match_the_event(): void
    {
        $this->assertTrue($this->makeServer('pc')->supportsRaceGame('ac'));
        $this->assertFalse($this->makeServer('console')->supportsRaceGame('ac'));
        $this->assertFalse($this->makeServer('pc')->supportsRaceGame('acc'));
        $this->assertTrue($this->makeServer('cross')->supportsRaceGame('acc'));
    }

    public function test_saving_a_pc_event_on_a_console_server_is_rejected(): void
    {
        $race = $this->makeRace('ac');
        $server = $this->makeServer('console');

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.races.update', $race), [
                'game' => 'ac', 'track' => 'Monza', 'status' => 'open', 'title' => 'Test Race',
                'scheduled_at' => $race->scheduled_at->timezone('Europe/London')->format('Y-m-d\TH:i'),
                'race_duration' => 30, 'ftp_server_id' => $server->id,
            ])
            ->assertSessionHasErrors(['ftp_server_id' => FtpServer::ERR_WRONG_PLATFORM]);

        $this->assertNull($race->fresh()->ftp_server_id);
    }

    public function test_csv_server_number_resolves_to_the_server_of_the_imports_platform(): void
    {
        $console = $this->makeServer('console');
        $pc = $this->makeServer('pc'); // same server_number (3) as the console one

        $csv = "track,date,time,server\r\nMonza,2026-10-01,20:00,3\r\n";
        $upload = fn (string $game) => $this->actingAs($this->makeAdmin())->post(route('admin.races.bulk-import-csv'), [
            'file' => UploadedFile::fake()->createWithContent('races.csv', $csv), 'game' => $game,
        ]);

        $upload('ac')->assertOk()->assertJsonPath('rows.0.ftp_server_id', $pc->id);
        $upload('acc')->assertOk()->assertJsonPath('rows.0.ftp_server_id', $console->id);
    }

    public function test_eligible_servers_match_the_events_platform_and_league(): void
    {
        $xclConsole = $this->makeServer('console');
        $xclPc = $this->makeServer('pc');
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $leagueConsole = $this->makeServer('cross', $league->id);
        $leaguePc = $this->makeServer('pc', $league->id);

        $this->assertSame([$xclPc->id], $this->makeRace('ac')->eligibleServers()->pluck('id')->all());
        $this->assertSame([$xclConsole->id], $this->makeRace('acc')->eligibleServers()->pluck('id')->all());

        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $round = $this->makeRace('acc');
        $round->update(['championship_id' => $championship->id]);

        $this->assertSame([$leagueConsole->id], $round->eligibleServers()->pluck('id')->all());
    }

    public function test_ftp_result_import_refuses_a_server_not_eligible_for_the_race(): void
    {
        $race = $this->makeRace('ac');
        $console = $this->makeServer('console');

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.races.results.ftp', $race), ['server_id' => $console->id, 'filename' => 'x.json'])
            ->assertSessionHas('error', FtpServer::ERR_NOT_FOR_RACE);
    }

    public function test_pc_server_is_never_a_crossplay_server(): void
    {
        $config = app(AccServerConfigService::class);

        $this->assertSame(0, $config->settings($this->makeRace('ac'), $this->makeServer('pc'))['isCrossplayServer']);
        $this->assertSame(1, $config->settings($this->makeRace('acc'), $this->makeServer('console'))['isCrossplayServer']);
    }

    public function test_config_json_is_utf16le_for_pc_servers_and_untouched_for_console(): void
    {
        $json = json_encode(['serverName' => 'XCL SERVER 4 - PC', 'note' => 'Huracán']);

        $pc = FtpService::encodeConfigFor($this->makeServer('pc'), $json);
        $this->assertSame("\xFF\xFE", substr($pc, 0, 2));
        $this->assertSame($json, mb_convert_encoding(substr($pc, 2), 'UTF-8', 'UTF-16LE'));
        // Round-trips through the same decoder the result import uses for server files.
        [$decoded, $error] = app(AccResultImportService::class)->decodeContent($pc, 'settings.json');
        $this->assertNull($error);
        $this->assertSame($json, $decoded);

        $this->assertSame($json, FtpService::encodeConfigFor($this->makeServer('console'), $json));
    }

    public function test_server_name_shows_the_platform(): void
    {
        $config = app(AccServerConfigService::class);

        $this->assertSame('XCL SERVER 3 - PC', $config->settings($this->makeRace('ac'), $this->makeServer('pc'))['serverName']);
        $this->assertSame(
            'XCL SERVER 3 - Playstation 5 & Xbox Series S/X',
            $config->settings($this->makeRace('acc'), $this->makeServer('console'))['serverName']
        );
    }

    public function test_bop_import_skips_car_names_outside_the_games_catalogue(): void
    {
        $file = UploadedFile::fake()->createWithContent('bop.json', json_encode([
            ['car_model' => 'BMW M4 GT3 (2022)', 'track' => 'monza', 'ballast_kg' => 5, 'restrictor' => 0],
            ['car_model' => 'BMW M4 GT3', 'track' => 'monza', 'ballast_kg' => 5, 'restrictor' => 0],
            ['carModel' => 30, 'track' => 'spa', 'ballastKg' => 3, 'restrictor' => 0],
        ]));

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.bops.import'), ['json_file' => $file, 'game' => 'ac', 'mode' => 'merge'])
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, '1 skipped') && str_contains($msg, 'BMW M4 GT3.'));

        // carModel 30 is the BMW M4 GT3 on PC.
        $this->assertEqualsCanonicalizing(
            ['monza', 'spa'],
            Bop::where('game', 'ac')->where('car_model', 'BMW M4 GT3 (2022)')->pluck('track')->all()
        );
        $this->assertSame(2, Bop::count());
    }
}
