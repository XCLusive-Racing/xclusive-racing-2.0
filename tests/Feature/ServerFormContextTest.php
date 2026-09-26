<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A league's servers are its own: the XCL-fleet naming hints (SERVER 1/2/3/4) only
// belong on XCL's Configuration > Servers, and editing a league server from "All
// League Servers" returns there — not to XCL's own server list.
class ServerFormContextTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        return $admin;
    }

    private function makeLeagueServer(): FtpServer
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);

        return FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'rolling', 'reset_start_hour' => 0, 'reset_interval_minutes' => 120,
            'league_id' => $league->id,
        ]);
    }

    public function test_xcl_hints_only_show_on_xcls_own_server_form(): void
    {
        $admin = $this->makeAdmin();
        $server = $this->makeLeagueServer();

        $this->actingAs($admin)->get(route('admin.servers.create'))
            ->assertOk()->assertSee('Rolling resets (SERVER 1 / 2 / 3)');

        $this->actingAs($admin)->get(route('admin.leagues.edit', $server->league_id))
            ->assertOk()->assertDontSee('SERVER 1 / 2 / 3')->assertSee('First Reset Hour (UK time)');

        $this->actingAs($admin)->get(route('admin.servers.edit', $server))
            ->assertOk()->assertDontSee('SERVER 1 / 2 / 3');
    }

    public function test_editing_a_league_server_returns_to_the_league_servers_list(): void
    {
        $admin = $this->makeAdmin();
        $server = $this->makeLeagueServer();

        $this->actingAs($admin)->get(route('admin.servers.edit', $server))
            ->assertOk()->assertSee(route('admin.league-servers.index'), false);

        $this->actingAs($admin)->put(route('admin.servers.update', $server), [
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21, 'path' => '/results',
            'server_type' => 'rolling', 'reset_start_hour' => 0, 'reset_interval_minutes' => 120,
        ])->assertRedirect(route('admin.league-servers.index'));
    }
}
