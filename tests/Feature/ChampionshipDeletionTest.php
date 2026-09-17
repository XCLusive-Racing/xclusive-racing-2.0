<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "Add the option to be able to remove your own
// championships in the admin panel championships overview" -- there was no
// destroy route at all before this, even though ChampionshipPolicy::delete()
// (canManage() or the league's own manager) already existed, unused.
class ChampionshipDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
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

    private function makeChampionship(League $league): Championship
    {
        return Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    public function test_a_league_manager_can_remove_their_own_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)
            ->delete(route('admin.leagues.championships.destroy', [$league, $championship]))
            ->assertRedirect(route('admin.leagues.championships.index', $league));

        $this->assertSoftDeleted('championships', ['id' => $championship->id]);
    }

    public function test_admin_can_remove_any_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $admin        = $this->makeAdmin();
        $championship = $this->makeChampionship($league);

        $this->actingAs($admin)
            ->delete(route('admin.leagues.championships.destroy', [$league, $championship]))
            ->assertRedirect();

        $this->assertSoftDeleted('championships', ['id' => $championship->id]);
    }

    public function test_a_league_steward_cannot_remove_a_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $steward      = User::factory()->leagueSteward()->create();
        $this->attach($steward, $league, 'steward');
        $championship = $this->makeChampionship($league);

        $this->actingAs($steward)
            ->delete(route('admin.leagues.championships.destroy', [$league, $championship]))
            ->assertForbidden();

        $this->assertDatabaseHas('championships', ['id' => $championship->id, 'deleted_at' => null]);
    }

    public function test_a_different_leagues_manager_cannot_remove_this_championship(): void
    {
        $league        = $this->makeLeague('nlrl');
        $otherLeague   = $this->makeLeague('src');
        $otherManager  = User::factory()->leagueManager()->create();
        $this->attach($otherManager, $otherLeague);
        $championship  = $this->makeChampionship($league);

        $this->actingAs($otherManager)
            ->delete(route('admin.leagues.championships.destroy', [$league, $championship]))
            ->assertNotFound();

        $this->assertDatabaseHas('championships', ['id' => $championship->id, 'deleted_at' => null]);
    }

    public function test_removed_championship_no_longer_lists_on_the_overview_page(): void
    {
        $league       = $this->makeLeague('nlrl');
        $manager      = User::factory()->leagueManager()->create();
        $this->attach($manager, $league);
        $championship = $this->makeChampionship($league);

        $this->actingAs($manager)->delete(route('admin.leagues.championships.destroy', [$league, $championship]));

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.index', $league))
            ->assertOk()
            ->assertSee('No championships yet');
    }
}
