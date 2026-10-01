<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use App\Services\FtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

// A league's own manager edits their own server's config defaults (event /
// settings / eventrules / assistrules JSON) and pushes them to the box — but only
// their own league's server, never XCL's fleet or another league's.
class LeagueServerConfigTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug),
            'slug' => $slug,
            'primary_color' => '#7c3aed',
            'accent_color' => '#db2777',
            'status' => 'draft',
        ]);
    }

    private function attach(User $user, League $league, string $role = 'manager'): User
    {
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $user->id, 'role' => $role]);
        $user->syncLeagueRoleFlags();

        return $user->refresh();
    }

    private function makeServer(League $league, string $name): FtpServer
    {
        return FtpServer::create([
            'name' => $name, 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results', 'cfg_path' => '/cfg',
            'server_type' => 'scheduled', 'league_id' => $league->id,
            'game' => 'acc', 'platform' => 'console',
        ]);
    }

    private function serverPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'path' => '/results', 'cfg_path' => '/cfg', 'server_type' => 'scheduled',
            'game' => 'acc', 'platform' => 'console',
        ];
    }

    private function expectPush(): void
    {
        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->once()->andReturn(true);
            foreach (['settings', 'eventrules', 'assistrules'] as $file) {
                $ftp->shouldReceive('uploadConfigFile')->once()->with("/cfg/{$file}.json", \Mockery::type('string'))->andReturn(true);
            }
            $ftp->shouldReceive('disconnect')->once();
        });
    }

    private function expectNoPush(): void
    {
        $this->mock(FtpService::class, fn (MockInterface $ftp) => $ftp->shouldNotReceive('connect'));
    }

    public function test_manager_sees_config_defaults_and_push_on_their_own_server_page(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');

        $this->actingAs($manager)->get(route('admin.leagues.servers.edit', [$league, $server]))
            ->assertOk()
            ->assertSee('Config Defaults')
            ->assertSee('settings.json')
            ->assertSee(route('admin.servers.push', $server));
    }

    public function test_manager_saves_a_config_override_on_their_own_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');

        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$league, $server]), $this->serverPayload([
            'settings_defaults' => json_encode(['maxCarSlots' => 24]),
            'eventrules_defaults' => '',
        ]))->assertRedirect(route('admin.leagues.edit', $league));

        $server->refresh();
        $this->assertSame(['maxCarSlots' => 24], $server->settings_defaults);
        $this->assertNull($server->eventrules_defaults);
    }

    public function test_invalid_json_is_rejected_and_nothing_is_saved(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');

        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$league, $server]), $this->serverPayload([
            'name' => 'Renamed',
            'settings_defaults' => '{not json',
        ]))->assertSessionHasErrors('settings_defaults');

        $this->assertSame('NLRL Server', $server->fresh()->name);
    }

    public function test_an_update_without_the_config_fields_keeps_existing_overrides(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');
        $server->update(['settings_defaults' => ['maxCarSlots' => 24]]);

        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$league, $server]), $this->serverPayload())
            ->assertRedirect();

        $this->assertSame(['maxCarSlots' => 24], $server->fresh()->settings_defaults);
    }

    public function test_manager_pushes_config_to_their_own_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');
        $this->expectPush();

        $this->actingAs($manager)
            ->from(route('admin.leagues.servers.edit', [$league, $server]))
            ->post(route('admin.servers.push', $server))
            ->assertRedirect(route('admin.leagues.servers.edit', [$league, $server]))
            ->assertSessionHas('success');
    }

    public function test_manager_cannot_push_to_another_leagues_or_xcls_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $foreign = $this->makeServer($this->makeLeague('src'), 'SRC Server');
        $xcl = $this->makeServer(League::system(), 'XCL Server 1');
        $this->expectNoPush();

        // TenantScope already hides both from route binding.
        $this->actingAs($manager)->post(route('admin.servers.push', $foreign))->assertNotFound();
        $this->actingAs($manager)->post(route('admin.servers.push', $xcl))->assertNotFound();
    }

    public function test_league_steward_cannot_push_to_their_leagues_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $steward = $this->attach(User::factory()->leagueSteward()->create(), $league, 'steward');
        $server = $this->makeServer($league, 'NLRL Server');
        $this->expectNoPush();

        $this->actingAs($steward)->post(route('admin.servers.push', $server))->assertForbidden();
    }

    public function test_driver_cannot_push(): void
    {
        $server = $this->makeServer($this->makeLeague('nlrl'), 'NLRL Server');
        $this->expectNoPush();

        // Route binding runs first and TenantScope hides the server, so 404 not 403.
        $this->actingAs(User::factory()->create())->post(route('admin.servers.push', $server))->assertNotFound();
    }

    public function test_manager_browses_views_and_saves_the_json_files_on_their_own_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $server = $this->makeServer($league, 'NLRL Server');

        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->andReturn(true);
            $ftp->shouldReceive('disconnect');
            $ftp->shouldReceive('listDirectory')->with('/cfg')->andReturn([]);
            $ftp->shouldReceive('getFileContent')->with('/cfg/settings.json')->andReturn('{"serverName":"NLRL"}');
            $ftp->shouldReceive('uploadFile')->once()->with('/cfg/settings.json', '{"serverName":"NLRL 2"}')->andReturn(true);
        });

        $this->actingAs($manager)->get(route('admin.servers.browse', ['ftpServer' => $server->id, 'path' => '/cfg']))
            ->assertOk()
            ->assertSee(route('admin.leagues.servers.edit', [$league, $server]));

        $this->actingAs($manager)->get(route('admin.servers.browse.view', ['ftpServer' => $server->id, 'path' => '/cfg/settings.json']))
            ->assertOk()
            ->assertSee('"serverName": "NLRL"', false);

        $this->actingAs($manager)->post(route('admin.servers.browse.save', $server), [
            'path' => '/cfg/settings.json', 'content' => '{"serverName":"NLRL 2"}',
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_manager_and_steward_cannot_browse_servers_that_arent_theirs(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = $this->attach(User::factory()->leagueManager()->create(), $league);
        $steward = $this->attach(User::factory()->leagueSteward()->create(), $league, 'steward');
        $own = $this->makeServer($league, 'NLRL Server');
        $foreign = $this->makeServer($this->makeLeague('src'), 'SRC Server');
        $xcl = $this->makeServer(League::system(), 'XCL Server 1');
        $this->expectNoPush();

        $this->actingAs($manager)->get(route('admin.servers.browse', $foreign))->assertNotFound();
        $this->actingAs($manager)->get(route('admin.servers.browse', $xcl))->assertNotFound();
        $this->actingAs($manager)->post(route('admin.servers.browse.save', $foreign), ['path' => '/x.json', 'content' => '{}'])->assertNotFound();
        $this->actingAs($steward)->get(route('admin.servers.browse', $own))->assertForbidden();
    }

    public function test_xcl_admin_still_pushes_any_server(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());
        $server = $this->makeServer(League::system(), 'XCL Server 1');
        $this->expectPush();

        $this->actingAs($admin)->post(route('admin.servers.push', $server))->assertSessionHas('success');
    }
}
