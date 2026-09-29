<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Message;
use App\Models\Race;
use App\Models\User;
use App\Services\AccServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Every numbered server used to be pushed as "XCL SERVER n" / "nxcl" — a league's
// own server too, so NLRL drivers were told to join "XCL SERVER 2". A server now
// has its own in-game name and password; a league server without them shows its
// own name, XCL's servers keep their naming.
class ServerIngameNameTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(): League
    {
        return League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeServer(int $leagueId, array $overrides = []): FtpServer
    {
        return FtpServer::create($overrides + [
            'name' => 'NLRL Server 2 Sunday', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'server_number' => 2,
            'league_id' => $leagueId, 'game' => 'acc', 'platform' => 'console',
        ]);
    }

    private function settings(FtpServer $server): array
    {
        return app(AccServerConfigService::class)->settings(new Race(['game' => 'acc']), $server);
    }

    public function test_xcl_servers_keep_their_naming(): void
    {
        $settings = $this->settings($this->makeServer(League::system()->id, ['name' => 'XCL SERVER 2']));

        $this->assertStringStartsWith('XCL SERVER 2 - ', $settings['serverName']);
        $this->assertSame('2xcl', $settings['password']);
    }

    public function test_a_league_server_shows_its_own_name_and_keeps_its_password_until_one_is_set(): void
    {
        $settings = $this->settings($this->makeServer($this->makeLeague()->id));

        $this->assertSame('NLRL Server 2 Sunday', $settings['serverName']);
        $this->assertSame('2xcl', $settings['password']);
    }

    public function test_the_ingame_name_and_password_win(): void
    {
        $league = $this->makeServer($this->makeLeague()->id, ['ingame_name' => 'NLRL | Sunday Cup', 'ingame_password' => 'green']);
        $xcl = $this->makeServer(League::system()->id, ['name' => 'XCL SERVER 1', 'server_number' => 1, 'ingame_name' => 'XCL Special', 'ingame_password' => 'secret']);

        $this->assertSame(['NLRL | Sunday Cup', 'green'], array_values(array_intersect_key($this->settings($league), array_flip(['serverName', 'password']))));
        $this->assertSame(['XCL Special', 'secret'], array_values(array_intersect_key($this->settings($xcl), array_flip(['serverName', 'password']))));
    }

    public function test_the_registration_inbox_message_names_the_league_server(): void
    {
        $server = $this->makeServer($this->makeLeague()->id, ['ingame_name' => 'NLRL | Sunday Cup', 'ingame_password' => 'green']);
        $race = Race::create([
            'title' => 'NLRL Round', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->addWeek(), 'ftp_server_id' => $server->id,
        ]);
        $driver = User::factory()->create(['platform_id' => 'X-1']);

        $this->actingAs($driver)->post(route('events.register', $race))->assertSessionHasNoErrors();

        $body = Message::where('user_id', $driver->id)->latest('id')->value('body');
        $this->assertStringContainsString('Server: NLRL | Sunday Cup', $body);
        $this->assertStringContainsString('Password: green', $body);
    }

    public function test_a_league_manager_sets_the_ingame_name_and_password(): void
    {
        $league = $this->makeLeague();
        $server = $this->makeServer($league->id);
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->actingAs($manager->refresh())
            ->put(route('admin.leagues.servers.update', [$league, $server]), [
                'name' => 'NLRL Server 2 Sunday', 'ingame_name' => 'NLRL | Sunday Cup', 'ingame_password' => 'green',
                'server_number' => 2, 'host' => '1.2.3.4', 'port' => 21, 'path' => '/results', 'cfg_path' => '/cfg',
                'server_type' => 'scheduled', 'game' => 'acc', 'platform' => 'console',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.leagues.edit', $league));

        $server->refresh();
        $this->assertSame('NLRL | Sunday Cup', $server->ingame_name);
        $this->assertSame('green', $server->ingame_password);

        $this->actingAs($manager)
            ->get(route('admin.leagues.servers.edit', [$league, $server]))
            ->assertOk()
            ->assertSee('value="NLRL | Sunday Cup"', false);
    }
}
