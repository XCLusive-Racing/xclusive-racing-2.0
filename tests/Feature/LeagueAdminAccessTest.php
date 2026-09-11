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

    // --- Archiving is a real soft delete, not just a status flag ---

    public function test_archiving_a_league_soft_deletes_it_and_it_disappears_from_a_plain_query(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();

        $this->assertSoftDeleted('leagues', ['id' => $league->id]);
        $this->assertNull(League::find($league->id));
    }

    public function test_archived_leagues_still_show_on_the_admin_index_for_restoring(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();

        $this->actingAs($owner)
            ->get(route('admin.leagues.index'))
            ->assertOk()
            ->assertSee('NLRL');
    }

    public function test_restoring_a_league_clears_the_soft_delete_and_resets_status_to_draft(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();

        $this->actingAs($owner)->post(route('admin.leagues.restore', $league))->assertRedirect();

        $league->refresh();
        $this->assertNull($league->deleted_at);
        $this->assertSame('draft', $league->status);
    }

    // --- Permanent delete (destroy()) — distinct from archive() ---

    public function test_only_an_already_archived_empty_league_can_be_permanently_deleted_by_an_owner(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertRedirect();

        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
    }

    public function test_a_non_archived_league_cannot_be_permanently_deleted(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();

        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertStatus(422);

        $this->assertDatabaseHas('leagues', ['id' => $league->id]);
    }

    public function test_an_admin_cannot_permanently_delete_a_league_even_when_archived(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $admin  = $this->makeAdmin();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.leagues.destroy', $league))->assertForbidden();

        $this->assertSoftDeleted('leagues', ['id' => $league->id]);
    }

    public function test_a_league_with_championships_cannot_be_permanently_deleted(): void
    {
        $league       = $this->makeLeague('nlrl');
        $owner        = $this->makeOwner();
        $championship = \App\Models\Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'settings' => \App\Settings\ChampionshipSettingsSchema::defaults(),
        ]);

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertStatus(422);

        $this->assertSoftDeleted('leagues', ['id' => $league->id]);
        $this->assertDatabaseHas('championships', ['id' => $championship->id]);
    }

    public function test_the_system_league_can_never_be_permanently_deleted(): void
    {
        $xcl   = League::system();
        $owner = $this->makeOwner();

        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $xcl))->assertForbidden();

        $this->assertDatabaseHas('leagues', ['id' => $xcl->id]);
    }

    public function test_the_general_edit_form_can_no_longer_set_status_to_archived(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin  = $this->makeAdmin();

        $this->actingAs($admin)->put(route('admin.leagues.update', $league), [
            'name' => 'NLRL', 'slug' => 'nlrl', 'status' => 'archived',
            'primary_color' => '#111111', 'accent_color' => '#222222',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $league->fresh()->status);
        $this->assertNull($league->fresh()->deleted_at);
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

    // --- Championship Manager: user-directed 2026-09 — "er moet een optie bij
    // de users tabel... waar wij ook rollen kunnen adden daar moet championship
    // manager komen" — a global role assigned from the Users admin page (not a
    // per-league membership row like manager/steward above) that acts as the
    // manager of every league at once, so granting access is a single toggle
    // rather than also needing a League Manager row added per league. ---

    public function test_championship_manager_sees_every_league_with_no_membership_rows_at_all(): void
    {
        $this->makeLeague('nlrl');
        $this->makeLeague('src');
        $manager = User::factory()->championshipManager()->create();

        $this->assertDatabaseMissing('league_user', ['user_id' => $manager->id]);

        $this->actingAs($manager)
            ->get(route('admin.leagues.index'))
            ->assertOk()
            ->assertSee('NLRL')
            ->assertSee('SRC');
    }

    public function test_championship_manager_can_edit_branding_but_not_identity_or_archive(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();

        $this->actingAs($manager)->put(route('admin.leagues.update', $league), [
            'primary_color' => '#111111',
            'accent_color'  => '#222222',
            'status'        => 'active', // attempted, must be ignored -- same as a per-league manager
            'name'          => 'Renamed', // attempted, must be ignored
        ])->assertRedirect(route('admin.leagues.edit', $league));

        $league->refresh();
        $this->assertSame('draft', $league->status);
        $this->assertSame('NLRL', $league->name);
        $this->assertSame('#111111', $league->primary_color);

        $this->actingAs($manager)->post(route('admin.leagues.archive', $league))->assertForbidden();
    }

    public function test_championship_manager_cannot_assign_league_roles(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();
        $recruit = User::factory()->create();

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id,
            'role'    => 'manager',
        ])->assertForbidden();
    }

    public function test_championship_manager_can_create_and_manage_a_championship_for_any_league(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.index', $league))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.store', $league))
            ->assertRedirect();

        $this->assertDatabaseHas('championships', ['league_id' => $league->id, 'name' => 'New Championship']);
    }

    // User-directed 2026-09: "ervoor zorgen dat we opties die bij championships
    // die ook bij leagues staan bij voorbeeld die require members discord to
    // join grayed out" — same locked pill-toggle look on both the league edit
    // page and the championship wizard's Requirements step (both use the exact
    // same "Temporarily locked" copy), instead of the league page's older plain
    // opacity-checkbox style.
    public function test_leagues_own_discord_toggle_is_locked_the_same_way_as_championships(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin  = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.leagues.edit', $league))
            ->assertOk()
            ->assertSee('disabled', false)
            ->assertSee('Temporarily locked — XCL is still finishing the operational Discord bot setup. Coming soon.');
    }

    // User-directed 2026-09: "de ftp bij leagues sectie van de admin page...
    // het liefst wil ik hem gwn exact hetzelfde als die van ons" -- the League
    // edit page's own "Add Your Own Server" form used to be a cramped,
    // different field set (no Server No., no Reset Schedule section) from
    // Configuration > Servers > Add Server. Both now render the exact same
    // shared partial (admin/servers/_add-server-fields.blade.php).
    public function test_league_edit_pages_add_server_form_matches_the_main_add_server_page(): void
    {
        $league = $this->makeLeague('nlrl');
        // Configuration > Servers is canManage()-only, unlike the league page's
        // own copy of this form (also reachable by that league's own manager) --
        // an admin actor here so both pages can actually be fetched for comparison.
        $admin = $this->makeAdmin();

        $mainPage   = $this->actingAs($admin)->get(route('admin.servers.create'));
        $leaguePage = $this->actingAs($admin)->get(route('admin.leagues.edit', $league));

        foreach (['Server No.', 'Reset Schedule', 'Rolling resets (SERVER 1 / 2 / 3)', 'Results Path', 'Config Path'] as $needle) {
            $mainPage->assertSee($needle);
            $leaguePage->assertSee($needle);
        }
    }

    public function test_league_managers_own_server_can_carry_a_server_number(): void
    {
        $league  = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.servers.create', $league), [
            'name' => 'League Server 1', 'server_number' => 2,
            'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'rolling',
            'reset_start_hour' => 1, 'reset_interval_minutes' => 120,
            'game' => 'acc', 'platform' => 'console',
        ])->assertRedirect();

        $this->assertDatabaseHas('ftp_servers', ['league_id' => $league->id, 'name' => 'League Server 1', 'server_number' => 2]);
    }

    // User-directed 2026-09: "sorry bij create leagues" -- the same option was
    // still a plain, fully live checkbox on the Create League page (never
    // updated to match), so a brand new league could actually have it turned
    // on despite the feature not being operational anywhere else. The lock
    // itself is UI-only (same as every other "locked" field in this app --
    // e.g. ChampionshipSettingsSchema's own copy of this option has no
    // corresponding backend rule either), so this only covers what a real
    // browser can actually submit through the rendered form -- the hidden
    // input always sending 0, not a server-side rejection of any other value.
    public function test_the_create_league_page_also_locks_the_discord_toggle(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.leagues.create'))
            ->assertOk()
            ->assertSee('disabled', false)
            ->assertSee('Temporarily locked — XCL is still finishing the operational Discord bot setup. Coming soon.');

        $this->actingAs($admin)->post(route('admin.leagues.store'), [
            'name' => 'EER', 'slug' => 'eer', 'primary_color' => '#111111', 'accent_color' => '#222222',
            'status' => 'draft', 'requires_discord_membership' => '0',
        ])->assertRedirect();

        $league = League::withoutTenantScope()->where('slug', 'eer')->firstOrFail();
        $this->assertFalse($league->requires_discord_membership);
    }

    // User-directed 2026-09: "Reset Interval (min) (rolling only) die mag weg
    // want ze hebben hun eigen servers... cfg_path (optional) moet required
    // zijn" -- both on the admin-only cross-league "All League Servers" Add
    // Server form (admin/league-servers/index.blade.php,
    // LeagueFtpServerController).
    public function test_league_servers_add_form_drops_reset_interval_and_requires_cfg_path(): void
    {
        $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.league-servers.index'))
            ->assertOk()
            ->assertDontSee('Reset Interval (min)')
            ->assertSee('Reset Start Hour');
    }

    // User-directed 2026-09: "bij cfg path mag je standaard /cfg neerzetten,
    // en bij platform mag je crossplay weghalen."
    public function test_league_servers_add_form_defaults_cfg_path_and_drops_crossplay(): void
    {
        $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.league-servers.index'))
            ->assertOk()
            ->assertSee('value="/cfg"', false)
            ->assertDontSee('Crossplay');
    }

    // User-directed 2026-09: "username en password mag je leeglaten want hij
    // pakt nu standaard mn email en password daarvan" -- the browser's own
    // saved login for this exact domain was autofilling into these unrelated
    // FTP credential fields, since they carried no autocomplete guard (the
    // main Add Server page's copy already had one).
    public function test_league_servers_add_form_guards_credential_fields_against_browser_autofill(): void
    {
        $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.league-servers.index'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('autocomplete="off"', false)
            ->assertSee('autocomplete="new-password"', false);
    }

    public function test_league_servers_store_no_longer_accepts_crossplay(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin  = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.league-servers.store'), [
            'league_id' => $league->id, 'name' => 'League Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results', 'cfg_path' => '/cfg',
            'server_type' => 'scheduled', 'game' => 'acc', 'platform' => 'cross',
        ])->assertSessionHasErrors('platform');

        $this->assertDatabaseMissing('ftp_servers', ['league_id' => $league->id, 'name' => 'League Server']);
    }

    public function test_league_servers_store_rejects_a_missing_cfg_path(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin  = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.league-servers.store'), [
            'league_id' => $league->id, 'name' => 'League Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'game' => 'acc', 'platform' => 'console',
        ])->assertSessionHasErrors('cfg_path');

        $this->assertDatabaseMissing('ftp_servers', ['league_id' => $league->id, 'name' => 'League Server']);
    }

    public function test_league_servers_store_no_longer_needs_a_reset_interval_and_defaults_to_120(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin  = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.league-servers.store'), [
            'league_id' => $league->id, 'name' => 'League Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results', 'cfg_path' => '/cfg',
            'server_type' => 'rolling', 'reset_start_hour' => 1,
            'game' => 'acc', 'platform' => 'console',
        ])->assertRedirect();

        $server = FtpServer::where('league_id', $league->id)->where('name', 'League Server')->firstOrFail();
        $this->assertSame(120, $server->reset_interval_minutes);
    }
}
