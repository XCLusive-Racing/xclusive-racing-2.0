<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name'          => strtoupper($slug),
            'slug'          => $slug,
            'primary_color' => '#7c3aed',
            'accent_color'  => '#db2777',
            'status'        => 'draft',
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

    private function makeOwner(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'owner')->first());

        return $user;
    }

    public function test_league_manager_cannot_create_a_league(): void
    {
        $manager = User::factory()->leagueManager()->create();

        $this->actingAs($manager)->get(route('admin.leagues.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.leagues.store'), [
            'name' => 'New League', 'slug' => 'newleague',
            'primary_color' => '#000000', 'accent_color' => '#ffffff', 'status' => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('leagues', ['slug' => 'newleague']);
    }

    public function test_league_manager_cannot_change_their_own_leagues_status(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->put(route('admin.leagues.update', $league), [
            'primary_color' => '#111111',
            'accent_color'  => '#222222',
            'status'        => 'active', // attempted, must be ignored
            'name'          => 'Renamed', // attempted, must be ignored
        ])->assertRedirect(route('admin.leagues.edit', $league));

        $league->refresh();
        $this->assertSame('draft', $league->status);
        $this->assertSame('NLRL', $league->name);
        $this->assertSame('#111111', $league->primary_color);
    }

    public function test_league_manager_cannot_archive_their_own_league(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.archive', $league))->assertForbidden();

        $this->assertSame('draft', $league->fresh()->status);
    }

    public function test_league_manager_cannot_assign_league_roles(): void
    {
        $league    = $this->makeLeague('nlrl');
        $manager   = User::factory()->leagueManager()->create();
        $recruit   = User::factory()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id,
            'role'    => 'manager',
        ])->assertForbidden();

        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $recruit->id]);
    }

    public function test_admin_can_create_edit_a_league_and_assign_members_but_not_archive_it(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.leagues.store'), [
            'name' => 'EER', 'slug' => 'eer', 'primary_color' => '#111111', 'accent_color' => '#222222', 'status' => 'draft',
        ])->assertRedirect();

        $league = League::withoutTenantScope()->where('slug', 'eer')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.leagues.update', $league), [
            'name' => 'EER', 'slug' => 'eer', 'status' => 'active',
            'primary_color' => '#333333', 'accent_color' => '#444444',
        ])->assertRedirect(route('admin.leagues.edit', $league));

        $this->assertSame('active', $league->fresh()->status);

        // Archiving is a league's "delete" — restricted to the owner role only,
        // even though an admin can otherwise fully manage a league.
        $this->actingAs($admin)->post(route('admin.leagues.archive', $league))->assertForbidden();
        $this->assertSame('active', $league->fresh()->status);

        $recruit = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id, 'role' => 'manager',
        ])->assertRedirect();

        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $recruit->id, 'role' => 'manager']);
        $this->assertTrue($recruit->fresh()->isLeagueManager());
    }

    public function test_only_the_owner_role_can_archive_a_league(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->assertSame('archived', $league->fresh()->status);
    }

    public function test_league_manager_edit_screen_never_renders_ftp_credentials(): void
    {
        $league = $this->makeLeague('nlrl');
        FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'super-secret-user', 'password' => 'super-secret-pass',
            'path' => '/results', 'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);

        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.servers.edit', FtpServer::first()));
        $response->assertOk();
        $response->assertDontSee('super-secret-user', false);
        $response->assertDontSee('super-secret-pass', false);
    }

    public function test_ftp_server_update_keeps_existing_username_and_password_when_left_blank(): void
    {
        $admin  = $this->makeAdmin();
        $server = FtpServer::create([
            'name' => 'Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'original-user', 'password' => 'original-pass',
            'path' => '/results', 'server_type' => 'scheduled',
        ]);

        $this->actingAs($admin)->put(route('admin.servers.update', $server), [
            'name' => 'Server', 'host' => '1.2.3.4', 'port' => 21,
            'path' => '/results', 'server_type' => 'scheduled',
            // username/password intentionally omitted
        ])->assertRedirect(route('admin.servers.index'));

        $server->refresh();
        $this->assertSame('original-user', $server->username);
        $this->assertSame('original-pass', $server->password);
    }
}
