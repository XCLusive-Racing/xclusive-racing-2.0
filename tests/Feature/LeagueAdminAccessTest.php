<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeagueAdminAccessTest extends TestCase
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

    // User-directed 2026-09: "give league managers themselves, permission to
    // publish or set active their league" -- status used to be admin/owner-only
    // (see the reverted test name below); a league's own manager can now flip
    // draft <-> active themselves, same as every other branding field, but
    // still never name/slug -- those stay XCL's call.
    public function test_league_manager_can_publish_their_own_league_but_not_rename_it(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->put(route('admin.leagues.update', $league), [
            'primary_color' => '#111111',
            'accent_color' => '#222222',
            'status' => 'active',
            'name' => 'Renamed', // attempted, must be ignored
        ])->assertRedirect(route('admin.leagues.edit', $league));

        $league->refresh();
        $this->assertSame('active', $league->status);
        $this->assertSame('NLRL', $league->name);
        $this->assertSame('#111111', $league->primary_color);
    }

    public function test_league_manager_cannot_archive_their_league_via_the_status_field(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->put(route('admin.leagues.update', $league), [
            'primary_color' => '#111111',
            'accent_color' => '#222222',
            'status' => 'archived',
        ])->assertSessionHasErrors('status');

        $this->assertSame('draft', $league->fresh()->status);
    }

    public function test_league_manager_cannot_archive_their_own_league(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.archive', $league))->assertForbidden();

        $this->assertSame('draft', $league->fresh()->status);
    }

    public function test_league_manager_cannot_assign_league_roles(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $recruit = User::factory()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id,
            'role' => 'manager',
        ])->assertForbidden();

        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $recruit->id]);
    }

    public function test_admin_can_create_edit_a_league_and_assign_members(): void
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

        $recruit = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id, 'role' => 'manager',
        ])->assertRedirect();

        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $recruit->id, 'role' => 'manager']);
        $this->assertTrue($recruit->fresh()->isLeagueManager());
    }

    // User-directed 2026-09: "give admin and owner the option to remove a
    // league" -- archiving/deleting a league was owner-only; admin now has
    // the same ability (a league manager/steward/championship manager still
    // cannot, per test_league_manager_cannot_archive_their_own_league above).
    public function test_owner_or_admin_can_archive_a_league(): void
    {
        $owner = $this->makeOwner();
        $this->actingAs($owner)
            ->post(route('admin.leagues.archive', $this->makeLeague('nlrl')))
            ->assertRedirect();

        $admin = $this->makeAdmin();
        $league = $this->makeLeague('src');
        $this->actingAs($admin)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->assertSame('archived', $league->fresh()->status);
    }

    // --- Archiving is a real soft delete, not just a status flag ---

    public function test_archiving_a_league_soft_deletes_it_and_it_disappears_from_a_plain_query(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();

        $this->assertSoftDeleted('leagues', ['id' => $league->id]);
        $this->assertNull(League::find($league->id));
    }

    public function test_archived_leagues_still_show_on_the_admin_index_for_restoring(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();
        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();

        $this->actingAs($owner)
            ->get(route('admin.leagues.index'))
            ->assertOk()
            ->assertSee('NLRL');
    }

    public function test_restoring_a_league_clears_the_soft_delete_and_resets_status_to_draft(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();
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
        $owner = $this->makeOwner();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertRedirect();

        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
    }

    public function test_a_non_archived_league_cannot_be_permanently_deleted(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();

        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertStatus(422);

        $this->assertDatabaseHas('leagues', ['id' => $league->id]);
    }

    public function test_an_admin_can_permanently_delete_an_archived_empty_league(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();
        $admin = $this->makeAdmin();

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.leagues.destroy', $league))->assertRedirect();

        $this->assertDatabaseMissing('leagues', ['id' => $league->id]);
    }

    public function test_a_league_with_championships_cannot_be_permanently_deleted(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner = $this->makeOwner();
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);

        $this->actingAs($owner)->post(route('admin.leagues.archive', $league))->assertRedirect();
        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $league))->assertStatus(422);

        $this->assertSoftDeleted('leagues', ['id' => $league->id]);
        $this->assertDatabaseHas('championships', ['id' => $championship->id]);
    }

    public function test_the_system_league_can_never_be_permanently_deleted(): void
    {
        $xcl = League::system();
        $owner = $this->makeOwner();

        $this->actingAs($owner)->delete(route('admin.leagues.destroy', $xcl))->assertForbidden();

        $this->assertDatabaseHas('leagues', ['id' => $xcl->id]);
    }

    public function test_the_general_edit_form_can_no_longer_set_status_to_archived(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();

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
        $admin = $this->makeAdmin();
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

    // Real incident, 2026-09: every FTP server's password had somehow ended up
    // encrypted under a since-rotated APP_KEY (unrelated data corruption, not
    // reproduced here) -- undecryptable under the current key. Re-entering a
    // fresh password through this exact form then threw DecryptException
    // ("The MAC is invalid") *on save*, before the new value was ever
    // written: Eloquent's dirty-check for an 'encrypted'-cast attribute
    // decrypts both the new value and the stored original to compare them,
    // so a corrupt original poisoned every future save too, with no way to
    // recover through the UI. Reproduced here with garbage ciphertext
    // written directly (bypassing the model, so Eloquent never touches it
    // until the update() call under test does).
    public function test_ftp_server_update_recovers_from_an_undecryptable_stored_password(): void
    {
        $admin = $this->makeAdmin();
        $server = FtpServer::create([
            'name' => 'Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'original-user', 'password' => 'original-pass',
            'path' => '/results', 'server_type' => 'scheduled',
        ]);
        DB::table('ftp_servers')->where('id', $server->id)
            ->update(['password' => 'not-valid-ciphertext-at-all']);

        $this->actingAs($admin)->put(route('admin.servers.update', $server), [
            'name' => 'Server', 'host' => '1.2.3.4', 'port' => 21,
            'path' => '/results', 'server_type' => 'scheduled',
            'username' => 'original-user', 'password' => 'brand-new-pass',
        ])->assertRedirect(route('admin.servers.index'));

        $this->assertSame('brand-new-pass', $server->fresh()->password);
    }

    // --- Championship Manager: user-directed 2026-09 — originally shipped as a
    // global "manager of every league at once" role (assigned from the Users
    // admin page, not a per-league membership row), but that meant anyone
    // holding it saw and managed every league/championship/server on the
    // platform regardless of membership -- flagged 2026-09-12 after real users
    // ended up with the role and zero league memberships, seeing everything.
    // Reworked so it only unlocks the leagues/championships admin area itself;
    // actually managing a given league still requires a real League Manager
    // membership row on it, same as anyone else. ---

    // User-directed 2026-09-12: a Championship Manager creates their own league(s)
    // self-service rather than an owner/admin doing it for them -- but they must
    // only ever end up seeing that league, not every other one already on the
    // platform (they have no membership row until they create one themselves).
    public function test_championship_manager_can_create_a_league_and_then_only_sees_that_one(): void
    {
        $this->makeLeague('other-league');
        $manager = User::factory()->championshipManager()->create();

        $this->actingAs($manager)->post(route('admin.leagues.store'), [
            'name' => 'My League', 'slug' => 'my-league',
            'primary_color' => '#111111', 'accent_color' => '#222222',
            'status' => 'draft',
        ])->assertRedirect();

        $league = League::where('slug', 'my-league')->firstOrFail();
        $this->assertDatabaseHas('league_user', ['user_id' => $manager->id, 'league_id' => $league->id, 'role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('admin.leagues.index'))
            ->assertRedirect(route('admin.leagues.edit', $league));

        $this->actingAs($manager)
            ->get(route('admin.leagues.edit', $league))
            ->assertOk();
    }

    public function test_championship_manager_can_edit_branding_and_publish_but_not_identity_or_archive_for_a_league_they_manage(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->put(route('admin.leagues.update', $league), [
            'primary_color' => '#111111',
            'accent_color' => '#222222',
            'status' => 'active', // now honored -- same as a per-league manager
            'name' => 'Renamed', // attempted, must be ignored
        ])->assertRedirect(route('admin.leagues.edit', $league));

        $league->refresh();
        $this->assertSame('active', $league->status);
        $this->assertSame('NLRL', $league->name);
        $this->assertSame('#111111', $league->primary_color);

        $this->actingAs($manager)->post(route('admin.leagues.archive', $league))->assertForbidden();
    }

    public function test_championship_manager_cannot_manage_a_league_they_are_not_a_member_of(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();

        $this->actingAs($manager)
            ->get(route('admin.leagues.edit', $league))
            ->assertNotFound();

        $this->actingAs($manager)
            ->put(route('admin.leagues.update', $league), ['primary_color' => '#111111', 'accent_color' => '#222222'])
            ->assertNotFound();
    }

    public function test_championship_manager_cannot_assign_league_roles(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();
        $this->attach($manager, $league);
        $recruit = User::factory()->create();

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $league), [
            'user_id' => $recruit->id,
            'role' => 'manager',
        ])->assertForbidden();
    }

    public function test_championship_manager_can_create_and_manage_a_championship_for_a_league_they_manage(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->championshipManager()->create();
        $this->attach($manager, $league);

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
        $admin = $this->makeAdmin();

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

        $mainPage = $this->actingAs($admin)->get(route('admin.servers.create'));
        $leaguePage = $this->actingAs($admin)->get(route('admin.leagues.edit', $league));

        foreach (['Server No.', 'Reset Schedule', 'Rolling resets (SERVER 1 / 2 / 3)', 'Results Path', 'Config Path'] as $needle) {
            $mainPage->assertSee($needle);
            $leaguePage->assertSee($needle);
        }
    }

    // User-directed 2026-09 (reverses the earlier staff-only rule): a league
    // manager adds and edits their own league's servers again — but only ever
    // sees their own league's, never XCL's fleet or another league's, and never
    // the stored FTP username/password.
    private function makeServer(League $league, string $name): FtpServer
    {
        return FtpServer::create([
            'name' => $name, 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'secret-user', 'password' => 'secret-pass', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
    }

    private function serverPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'League Server 1', 'server_number' => 2,
            'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'cfg_path' => '/cfg', 'server_type' => 'rolling',
            'reset_start_hour' => 1, 'reset_interval_minutes' => 120,
            'game' => 'acc', 'platform' => 'console',
        ];
    }

    public function test_league_manager_sees_only_their_own_leagues_servers(): void
    {
        $league = $this->makeLeague('nlrl');
        $other = $this->makeLeague('src');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);
        $this->makeServer($league, 'NLRL Server');
        $this->makeServer($other, 'SRC Server');
        $this->makeServer(League::system(), 'XCL Server 1');

        $this->actingAs($manager)->get(route('admin.leagues.edit', $league))
            ->assertOk()
            ->assertSee('League Servers')
            ->assertSee('NLRL Server')
            ->assertSee('Add Your Own Server')
            ->assertDontSee('SRC Server')
            ->assertDontSee('XCL Server 1')
            ->assertDontSee('Assign Server')
            ->assertDontSee('secret-user');
    }

    public function test_league_manager_adds_and_edits_their_own_server_but_cannot_remove_it(): void
    {
        $league = $this->makeLeague('nlrl');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);

        $this->actingAs($manager)->post(route('admin.leagues.servers.create', $league), $this->serverPayload())
            ->assertRedirect();
        $server = FtpServer::withoutTenantScope()->where('name', 'League Server 1')->firstOrFail();
        $this->assertSame($league->id, $server->league_id);

        $this->actingAs($manager)->get(route('admin.leagues.servers.edit', [$league, $server]))
            ->assertOk()
            ->assertSee('value="League Server 1"', false)
            ->assertDontSee('value="u"', false);

        // Blank credentials keep the stored ones.
        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$league, $server]),
            $this->serverPayload(['name' => 'Renamed', 'username' => '', 'password' => '']))
            ->assertRedirect(route('admin.leagues.edit', $league));
        $server->refresh();
        $this->assertSame('Renamed', $server->name);
        $this->assertSame('p', $server->password);

        $this->actingAs($manager)
            ->delete(route('admin.leagues.servers.destroy', [$league, $server]))
            ->assertForbidden();
    }

    public function test_league_manager_cannot_edit_another_leagues_server(): void
    {
        $league = $this->makeLeague('nlrl');
        $other = $this->makeLeague('src');
        $manager = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);
        $foreign = $this->makeServer($other, 'SRC Server');

        // Through their own league's URL, and through the other league's (which
        // TenantScope already hides from them entirely, so 404 there too).
        $this->actingAs($manager)->get(route('admin.leagues.servers.edit', [$league, $foreign]))->assertNotFound();
        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$league, $foreign]), $this->serverPayload())->assertNotFound();
        $this->actingAs($manager)->put(route('admin.leagues.servers.update', [$other, $foreign]), $this->serverPayload())->assertNotFound();
        $this->actingAs($manager)->post(route('admin.leagues.servers.create', $other), $this->serverPayload())->assertNotFound();

        $this->assertSame('SRC Server', $foreign->fresh()->name);
        $this->assertDatabaseMissing('ftp_servers', ['name' => 'League Server 1']);
    }

    public function test_admin_still_sees_and_manages_the_league_servers_card(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.leagues.edit', $league))
            ->assertOk()
            ->assertSee('League Servers')
            ->assertSee('Add Your Own Server');

        $this->actingAs($admin)->post(route('admin.leagues.servers.create', $league), [
            'name' => 'League Server 1', 'server_number' => 2,
            'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'rolling',
            'reset_start_hour' => 1, 'reset_interval_minutes' => 120,
            'game' => 'acc', 'platform' => 'console',
        ])->assertRedirect();

        $this->assertDatabaseHas('ftp_servers', ['league_id' => $league->id, 'name' => 'League Server 1', 'server_number' => 2]);
    }

    // User-directed 2026-09: "Configuration -> FTP Servers should just be our
    // own servers, which are XCL SERVER" -- FtpServerController::index() had
    // no league_id filter at all, so any league's own dedicated server (e.g.
    // NLRL's) leaked into XCL's core-fleet Configuration page.
    public function test_configuration_ftp_servers_page_only_shows_xcls_own_servers(): void
    {
        $league = $this->makeLeague('nlrl');
        $admin = $this->makeAdmin();
        FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        FtpServer::create([
            'name' => 'XCL SERVER 1', 'host' => '5.6.7.8', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => League::system()->id,
        ]);

        $this->actingAs($admin)->get(route('admin.servers.index'))
            ->assertOk()
            ->assertSee('XCL SERVER 1')
            ->assertDontSee('NLRL Server');
    }

    public function test_a_server_added_from_configuration_is_tagged_as_xcls_own(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post(route('admin.servers.store'), [
            'name' => 'XCL SERVER 6', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'rolling', 'reset_start_hour' => 0, 'reset_interval_minutes' => 120,
            'game' => 'acc', 'platform' => 'console',
        ])->assertRedirect(route('admin.servers.index'));

        $this->assertDatabaseHas('ftp_servers', ['name' => 'XCL SERVER 6', 'league_id' => League::system()->id]);
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
        $admin = $this->makeAdmin();

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
        $admin = $this->makeAdmin();

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
        $admin = $this->makeAdmin();

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
