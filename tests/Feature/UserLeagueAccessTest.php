<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "kunnen we dat combineren" (in the context of "wat is
// league manager en championship manager" -- can granting per-league access be
// combined with the global-role assignment already on the Users edit page,
// instead of needing a separate trip to each league's own edit page). Same
// underlying action and gate as LeagueController::addMember()/removeMember()
// (canManage()), just reachable from the user's own edit page too now.
class UserLeagueAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    // Owner, not admin: /admin/users/* is deliberately owner/moderator/
    // event_manager only (routes/web.php) -- a plain 'admin' role can't reach
    // the Users edit page at all, canManage() or not.
    private function makeOwner(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'owner')->first());
        return $user;
    }

    private function makeModerator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'moderator')->first());
        return $user;
    }

    private function baseUserUpdatePayload(User $user): array
    {
        return [
            'name' => $user->name, 'email' => $user->email,
            'elo_acc' => 0, 'elo_lmu' => 0, 'elo_iracing' => 0,
            'sr_acc' => 0, 'sr_lmu' => 0, 'sr_iracing' => 0,
        ];
    }

    public function test_edit_screen_shows_league_access_selects_for_a_canmanage_user(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $target = User::factory()->create();

        $this->actingAs($owner)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('League Access')
            ->assertSee('NLRL')
            ->assertSee('league_roles[' . $league->id . ']', false);
    }

    public function test_edit_screen_hides_league_access_from_a_moderator_who_cant_canmanage(): void
    {
        $this->makeLeague('nlrl');
        $moderator = $this->makeModerator();
        $target    = User::factory()->create();

        // A moderator can manage global roles (canManageRoles()) but not
        // canManage() -- the gate League Access actually needs, same as
        // LeagueController::addMember().
        $this->actingAs($moderator)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertDontSee('League Access');
    }

    public function test_saving_a_league_role_creates_a_membership_and_syncs_the_role_flag(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $target = User::factory()->create();

        $this->actingAs($owner)->put(route('admin.users.update', $target), array_merge(
            $this->baseUserUpdatePayload($target),
            ['league_roles' => [$league->id => 'manager']]
        ))->assertRedirect();

        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $target->id, 'role' => 'manager']);
        $this->assertTrue($target->fresh()->isLeagueManager());
    }

    public function test_changing_a_league_role_updates_the_existing_membership(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $target = User::factory()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $target->id, 'role' => 'steward']);

        $this->actingAs($owner)->put(route('admin.users.update', $target), array_merge(
            $this->baseUserUpdatePayload($target),
            ['league_roles' => [$league->id => 'manager']]
        ))->assertRedirect();

        $this->assertDatabaseHas('league_user', ['league_id' => $league->id, 'user_id' => $target->id, 'role' => 'manager']);
        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $target->id, 'role' => 'steward']);
    }

    public function test_selecting_none_removes_an_existing_membership(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();
        $target = User::factory()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $target->id, 'role' => 'manager']);
        $target->syncLeagueRoleFlags();

        $this->actingAs($owner)->put(route('admin.users.update', $target), array_merge(
            $this->baseUserUpdatePayload($target),
            ['league_roles' => [$league->id => '']]
        ))->assertRedirect();

        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $target->id]);
        $this->assertFalse($target->fresh()->isLeagueManager());
    }

    public function test_a_moderator_cannot_smuggle_league_roles_through_the_update_request(): void
    {
        $league    = $this->makeLeague('nlrl');
        $moderator = $this->makeModerator();
        $target    = User::factory()->create();

        $this->actingAs($moderator)->put(route('admin.users.update', $target), array_merge(
            $this->baseUserUpdatePayload($target),
            ['league_roles' => [$league->id => 'manager']]
        ))->assertRedirect();

        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $target->id]);
    }

    public function test_an_owner_cannot_change_their_own_league_access(): void
    {
        $league = $this->makeLeague('nlrl');
        $owner  = $this->makeOwner();

        $this->actingAs($owner)->put(route('admin.users.update', $owner), array_merge(
            $this->baseUserUpdatePayload($owner),
            ['league_roles' => [$league->id => 'manager']]
        ))->assertRedirect();

        $this->assertDatabaseMissing('league_user', ['league_id' => $league->id, 'user_id' => $owner->id]);
    }
}
