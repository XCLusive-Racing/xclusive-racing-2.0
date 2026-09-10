<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name'          => strtoupper($slug),
            'slug'          => $slug,
            'primary_color' => '#7c3aed',
            'accent_color'  => '#db2777',
            'status'        => 'active',
        ]);
    }

    private function attach(User $user, League $league, string $role = 'manager'): void
    {
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $user->id, 'role' => $role]);
        $user->syncLeagueRoleFlags();
        $user->refresh();
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    // --- Direct model queries ---

    public function test_league_manager_cannot_query_another_leagues_row_directly(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);

        $this->actingAs($manager);

        $this->assertNull(League::find($src->id));
        $this->assertTrue(League::all()->pluck('id')->contains($nlrl->id));
        $this->assertFalse(League::all()->pluck('id')->contains($src->id));
    }

    public function test_user_with_no_league_membership_sees_no_leagues(): void
    {
        $this->makeLeague('nlrl');
        $this->makeLeague('src');

        $driver = User::factory()->create();
        $this->actingAs($driver);

        $this->assertCount(0, League::all());
        $this->assertNull(League::first());
    }

    public function test_admin_sees_every_league(): void
    {
        $this->makeLeague('nlrl');
        $this->makeLeague('src');

        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        // +1 for the XCL system league every migration run creates (Phase 2.5).
        $this->assertCount(3, League::all());
    }

    public function test_console_context_without_a_user_sees_no_leagues_unless_bypassed(): void
    {
        $this->makeLeague('nlrl');

        // No actingAs() — simulates a console/job context with no authenticated user.
        $this->assertCount(0, League::all());
        // +1 for the XCL system league every migration run creates (Phase 2.5).
        $this->assertCount(2, League::withoutTenantScope()->get());
    }

    // --- HTTP routes: cross-league access must 404, not 403 ---
    // A 403 would confirm the record exists; a 404 does not.

    public function test_league_manager_gets_404_not_403_for_another_leagues_edit_page(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);

        $this->actingAs($manager)
            ->get(route('admin.leagues.edit', $src))
            ->assertNotFound();
    }

    public function test_league_manager_gets_404_not_403_for_another_leagues_update_route(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);

        $this->actingAs($manager)
            ->put(route('admin.leagues.update', $src), [
                'primary_color' => '#000000',
                'accent_color'  => '#ffffff',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('leagues', ['id' => $src->id, 'primary_color' => '#7c3aed']);
    }

    public function test_league_manager_can_reach_their_own_leagues_edit_page(): void
    {
        $nlrl = $this->makeLeague('nlrl');

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);

        $this->actingAs($manager)
            ->get(route('admin.leagues.edit', $nlrl))
            ->assertOk();
    }

    // Route-model binding resolves (and scopes) the League before the league.access
    // middleware runs, so a driver with no league role at all gets the same 404 as
    // a league manager probing a league that isn't theirs — a driver outside the
    // leagues system entirely can't even tell the admin area exists.
    public function test_driver_with_no_league_role_gets_404_on_a_leagues_edit_page(): void
    {
        $nlrl   = $this->makeLeague('nlrl');
        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->get(route('admin.leagues.edit', $nlrl))
            ->assertNotFound();
    }

    public function test_driver_with_no_league_role_is_forbidden_from_the_leagues_index(): void
    {
        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->get(route('admin.leagues.index'))
            ->assertForbidden();
    }

    // --- FtpServer: nullable league_id, isolated the same way ---

    public function test_league_manager_cannot_see_another_leagues_ftp_server(): void
    {
        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $srcServer = FtpServer::create([
            'name' => 'SRC Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $src->id,
        ]);

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);
        $this->actingAs($manager);

        $this->assertNull(FtpServer::find($srcServer->id));
    }

    public function test_league_manager_sees_their_own_leagues_ftp_server(): void
    {
        $nlrl = $this->makeLeague('nlrl');

        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $nlrl->id,
        ]);

        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $nlrl);
        $this->actingAs($manager);

        $this->assertNotNull(FtpServer::find($server->id));
    }

    // Since Phase 2.5, XCL is a real League row, not `league_id = NULL` — so it is
    // no longer special-cased by the scope itself. A plain driver with no league
    // membership at all sees zero league-owned rows via a scoped query, XCL's own
    // included, exactly like League itself already behaved. Anywhere that must
    // still show XCL's own data to everyone regardless of tenant (e.g.
    // RaceController::register() loading a race's FTP server) does so via an
    // explicit ->withoutTenantScope() at that read site, not through this scope.
    public function test_a_plain_driver_does_not_see_xcls_own_ftp_server_via_a_scoped_query(): void
    {
        $xclServer = FtpServer::create([
            'name' => 'XCL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => League::system()->id,
        ]);

        $driver = User::factory()->create();
        $this->actingAs($driver);

        $this->assertNull(FtpServer::find($xclServer->id));
        $this->assertNotNull(FtpServer::withoutTenantScope()->find($xclServer->id));
    }

    public function test_a_plain_driver_cannot_see_a_leagues_ftp_server(): void
    {
        $nlrl = $this->makeLeague('nlrl');

        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $nlrl->id,
        ]);

        $driver = User::factory()->create();
        $this->actingAs($driver);

        $this->assertNull(FtpServer::find($server->id));
    }
}
