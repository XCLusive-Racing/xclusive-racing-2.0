<?php

namespace Tests\Feature;

use App\Models\League;
use App\Models\LeagueUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "league of championship managers kunnen de admin
// button niet zien" -- the ADMIN nav button (User::canAccessAdminPanel(),
// rendered from resources/views/components/navbar.blade.php, the <x-navbar/>
// component layouts/app.blade.php actually uses -- layouts/_navbar.blade.php
// is a separate, unused file with its own copy of the same check) already
// listed every real role but 'driver', including league_manager/
// league_steward/championship_manager -- confirmed correct on inspection and
// with a full HTTP render as each role, not just the model method in
// isolation. Whatever the user was seeing wasn't reproducible from the code
// itself; these lock in the intended rule going forward: "elke role behalve
// driver mag admin button zien."
class AdminButtonVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_manager_sees_the_admin_button(): void
    {
        $league  = League::create(['name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active']);
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        $manager->refresh();

        $this->actingAs($manager)->get('/')->assertOk()->assertSee('ADMIN');
    }

    public function test_league_steward_sees_the_admin_button(): void
    {
        $league  = League::create(['name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active']);
        $steward = User::factory()->leagueSteward()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $steward->id, 'role' => 'steward']);
        $steward->syncLeagueRoleFlags();
        $steward->refresh();

        $this->actingAs($steward)->get('/')->assertOk()->assertSee('ADMIN');
    }

    public function test_championship_manager_sees_the_admin_button(): void
    {
        $cm = User::factory()->championshipManager()->create();

        $this->actingAs($cm)->get('/')->assertOk()->assertSee('ADMIN');
    }

    public function test_a_driver_with_no_staff_role_does_not_see_the_admin_button(): void
    {
        $driver = User::factory()->create();

        $this->actingAs($driver)->get('/')->assertOk()->assertDontSee('ADMIN');
    }
}
