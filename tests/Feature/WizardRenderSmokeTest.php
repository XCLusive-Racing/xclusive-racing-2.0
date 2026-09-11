<?php
namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardRenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_wizard_step_renders_with_the_new_grouped_layout(): void
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#7c3aed',
            'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        $manager->refresh();

        foreach (array_keys(ChampionshipSettingsSchema::STEPS) as $step) {
            $response = $this->actingAs($manager)
                ->get(route('admin.leagues.championships.wizard', [$league, $championship, $step]));
            $response->assertOk();
        }

        // Spot-check the section headers actually appear where expected.
        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'format']))
            ->assertSee('Driver Swaps');

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'requirements']))
            ->assertSee('Entry Requirements')
            ->assertSee('Registration')
            ->assertDontSee('Championship Rules');

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'penalties']))
            ->assertSee('Stewarding &amp; Penalties', false)
            ->assertSee('Balance &amp; Adjustments', false);
    }
}
