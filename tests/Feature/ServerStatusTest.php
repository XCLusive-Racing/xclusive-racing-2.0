<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpImportedFile;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\Role;
use App\Models\User;
use App\Services\ServerStatusService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Admin Server Status page: per server the last push, next race, last result
// import and practice, flagging failed pushes, overdue results and practice errors.
class ServerStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeServer(string $name, array $overrides = []): FtpServer
    {
        return FtpServer::create(array_merge([
            'name' => $name, 'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'scheduled', 'game' => 'acc', 'platform' => 'console', 'active' => true,
        ], $overrides));
    }

    private function makeRace(FtpServer $server, string $title, array $overrides = []): Race
    {
        return Race::create(array_merge([
            'title' => $title, 'track' => 'monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->addDay(), 'ftp_server_id' => $server->id,
        ], $overrides));
    }

    private function row(FtpServer $server): array
    {
        return app(ServerStatusService::class)->rows()->firstWhere(fn ($row) => $row['server']->is($server));
    }

    public function test_a_healthy_server_shows_its_last_push_next_race_and_last_import(): void
    {
        $server = $this->makeServer('Server 1');
        $past = $this->makeRace($server, 'Last night', [
            'status' => 'finished', 'scheduled_at' => now()->subHours(20),
            'config_pushed_at' => now()->subHours(20), 'config_push_status' => 'pushed',
        ]);
        RaceResult::create(['race_id' => $past->id, 'session_type' => 'race', 'driver_name' => 'X', 'player_id' => 'M1',
            'position' => 1, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false]);
        FtpImportedFile::create(['ftp_server_id' => $server->id, 'race_id' => $past->id, 'filename' => '260926_200000_R.json']);
        $this->makeRace($server, 'Tomorrow', ['config_push_status' => 'pending']);

        $row = $this->row($server);

        $this->assertSame(ServerStatusService::HEALTH_OK, $row['health']);
        $this->assertSame('Last night', $row['last_push']->title);
        $this->assertSame('Tomorrow', $row['next_race']->title);
        $this->assertSame('Last night', $row['last_import']->race->title);
        $this->assertSame([], $row['problems']);
    }

    public function test_failed_pushes_overdue_results_and_practice_errors_need_a_look(): void
    {
        $server = $this->makeServer('Server 2');
        $this->makeRace($server, 'Failed push', ['config_push_status' => 'failed', 'config_push_error' => 'Could not connect']);
        $noResults = $this->makeRace($server, 'No results', ['status' => 'closed', 'scheduled_at' => now()->subHours(3)]);
        RaceRegistration::create(['race_id' => $noResults->id, 'user_id' => User::factory()->create()->id]);
        // Nobody signed up, so no results are expected.
        $this->makeRace($server, 'Empty race', ['status' => 'finished', 'scheduled_at' => now()->subHours(5)]);
        // Only just started — results aren't due yet.
        $justStarted = $this->makeRace($server, 'Just started', ['status' => 'closed', 'scheduled_at' => now()->subMinutes(40)]);
        RaceRegistration::create(['race_id' => $justStarted->id, 'user_id' => User::factory()->create()->id]);

        $championship = Championship::create([
            'league_id' => League::system()->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026, 'status' => 'running',
            'visibility' => 'public', 'ftp_server_id' => $server->id, 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        Championship::withoutTenantScope()->whereKey($championship->id)
            ->update(['practice_pushed_at' => now(), 'practice_push_error' => 'Upload failed: event.json']);

        $row = $this->row($server);

        $this->assertSame(ServerStatusService::HEALTH_WARNING, $row['health']);
        $this->assertSame(['1 failed config push', '1 race without results', 'Practice push failed'], $row['problems']);
        $this->assertSame(['No results'], $row['overdue_results']->pluck('title')->all());
        $this->assertSame('Cup', $row['championship_practice']->name);
    }

    public function test_the_page_lists_problem_servers_first_and_is_admin_only(): void
    {
        $this->makeServer('Quiet Server');
        $this->makeServer('Old Server', ['active' => false]);
        $broken = $this->makeServer('Broken Server');
        $this->makeRace($broken, 'Failed push', ['config_push_status' => 'failed']);

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $html = $this->actingAs($admin)
            ->get(route('admin.servers.status'))
            ->assertOk()
            ->assertSee('1 need a look')
            ->getContent();

        $this->assertTrue(strpos($html, 'Broken Server') < strpos($html, 'Quiet Server'));
        $this->assertTrue(strpos($html, 'Quiet Server') < strpos($html, 'Old Server'));

        $this->actingAs(User::factory()->create())->get(route('admin.servers.status'))->assertForbidden();
    }
}
