<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\PointsScheme;
use App\Models\Race;
use App\Models\Report;
use App\Models\SessionFormat;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback 2026-10-25: a league's own admin (edits championships, servers and
// event settings, never creates or deletes them) next to its stewards (incidents
// only). The league's manager hands both out; adding a manager stays XCL staff.
class LeagueAdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function member(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $user->id, 'role' => $role]);
        $user->syncLeagueRoleFlags();

        // Logged in: the policies read the league through its tenant scope.
        $this->actingAs($user->refresh());

        return $user;
    }

    private function championship(): Championship
    {
        return Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    public function test_a_league_admin_edits_championships_but_never_creates_or_deletes_them(): void
    {
        $admin = $this->member('admin');
        $championship = $this->championship();

        $this->assertTrue($admin->isLeagueAdmin());
        $this->assertTrue($admin->can('update', $championship));
        $this->assertFalse($admin->can('create', [Championship::class, $this->league]));
        $this->assertFalse($admin->can('delete', $championship));

        $this->actingAs($admin)->get(route('admin.leagues.championships.index', $this->league))->assertOk();
        $this->actingAs($admin)->post(route('admin.leagues.championships.store', $this->league))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.leagues.championships.destroy', [$this->league, $championship]))->assertForbidden();
        $this->assertNotNull($championship->fresh());
    }

    public function test_a_league_steward_only_views_championships(): void
    {
        $steward = $this->member('steward');
        $championship = $this->championship();

        $this->assertTrue($steward->can('view', $championship));
        $this->assertFalse($steward->can('update', $championship));
    }

    public function test_a_league_admin_edits_event_settings_but_does_not_add_or_delete_them(): void
    {
        $admin = $this->member('admin');
        $format = SessionFormat::create(['league_id' => $this->league->id, 'name' => 'Sprint', 'race_durations' => [25], 'pitstop_count' => 0]);
        $scheme = PointsScheme::create(['league_id' => $this->league->id, 'name' => 'NLRL Points', 'type' => 'manual', 'points_table' => [1 => 25, 2 => 18]]);

        $this->actingAs($admin)->get(route('admin.leagues.session-formats.edit', [$this->league, $format]))->assertOk();
        $this->actingAs($admin)->get(route('admin.leagues.session-formats.create', $this->league))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.leagues.session-formats.destroy', [$this->league, $format]))->assertForbidden();

        $this->assertTrue($admin->can('update', $scheme));
        $this->assertFalse($admin->can('delete', $scheme));
        $this->actingAs($admin)->get(route('admin.leagues.points-schemes.create', $this->league))->assertForbidden();
    }

    public function test_a_league_admin_edits_the_leagues_servers_but_does_not_add_one(): void
    {
        $admin = $this->member('admin');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21, 'username' => 'user', 'password' => 'pass',
            'path' => '/results', 'server_type' => 'scheduled', 'league_id' => $this->league->id,
        ]);

        $this->assertTrue($admin->can('manage', $server));
        $this->actingAs($admin)->get(route('admin.leagues.servers.edit', [$this->league, $server]))->assertOk();
        $this->actingAs($admin)->post(route('admin.leagues.servers.create', $this->league), [
            'name' => 'Extra', 'host' => '5.6.7.8', 'port' => 21, 'username' => 'u', 'password' => 'p', 'path' => '/', 'server_type' => 'scheduled',
        ])->assertForbidden();

        // The league's own profile stays the manager's.
        $this->actingAs($admin)->put(route('admin.leagues.update', $this->league), [
            'primary_color' => '#000000', 'accent_color' => '#111111',
        ])->assertForbidden();
    }

    public function test_the_manager_adds_and_removes_admins_and_stewards_but_never_a_manager(): void
    {
        $manager = $this->member('manager');
        $recruit = User::factory()->create();

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $this->league), ['user_id' => $recruit->id, 'role' => 'admin'])
            ->assertRedirect();
        $this->assertTrue($recruit->fresh()->editsLeague($this->league));
        $this->assertTrue($recruit->fresh()->isLeagueAdmin());

        $this->actingAs($manager)->post(route('admin.leagues.members.store', $this->league), ['user_id' => $recruit->id, 'role' => 'manager'])
            ->assertForbidden();
        $this->assertFalse($recruit->fresh()->managesLeague($this->league));

        $otherManager = $this->member('manager');
        $managerRow = LeagueUser::where('user_id', $otherManager->id)->sole();
        $this->actingAs($manager)->delete(route('admin.leagues.members.destroy', [$this->league, $managerRow]))->assertForbidden();

        $adminRow = LeagueUser::where('user_id', $recruit->id)->sole();
        $this->actingAs($manager)->delete(route('admin.leagues.members.destroy', [$this->league, $adminRow]))->assertRedirect();
        $this->assertFalse($recruit->fresh()->isLeagueAdmin());
    }

    public function test_a_league_admin_cannot_manage_members(): void
    {
        $admin = $this->member('admin');

        $this->actingAs($admin)->post(route('admin.leagues.members.store', $this->league), ['user_id' => User::factory()->create()->id, 'role' => 'steward'])
            ->assertForbidden();
    }

    public function test_every_league_role_handles_its_own_leagues_incidents(): void
    {
        $race = Race::create(['championship_id' => $this->championship()->id, 'title' => 'R1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $reported = User::factory()->create();
        $report = Report::create([
            'user_id' => User::factory()->create()->id, 'race_id' => $race->id, 'reported_user_id' => $reported->id,
            'reported_driver_name' => $reported->name, 'description' => 'Contact at turn 1.', 'session_type' => 'R',
        ]);

        foreach (['manager', 'admin', 'steward'] as $role) {
            $this->actingAs($this->member($role))->get(route('admin.reports.show', $report))->assertOk();
        }
    }

    public function test_league_admins_join_the_entrylist_as_server_admins_but_stewards_do_not(): void
    {
        $this->member('manager', ['platform_id' => 'M-MANAGER']);
        $this->member('admin', ['platform_id' => 'M-ADMIN']);
        $this->member('steward', ['platform_id' => 'M-STEWARD']);
        $race = Race::create([
            'championship_id' => $this->championship()->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);

        $admins = collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->where('isServerAdmin', 1)->pluck('drivers.0.playerID')->sort()->values()->all();

        $this->assertSame(['M-ADMIN', 'M-MANAGER'], $admins);
    }
}
