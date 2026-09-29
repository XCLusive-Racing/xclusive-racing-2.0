<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: the events dashboard's Championships tab lists the upcoming
// rounds of public championships instead of a "coming soon" placeholder.
class SidebarChampionshipRoundsTest extends TestCase
{
    use RefreshDatabase;

    private function championship(string $name, string $status, string $visibility = 'public'): Championship
    {
        $league = League::withoutTenantScope()->firstOrCreate(['slug' => 'nlrl'], [
            'name' => 'NLRL', 'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);

        return Championship::withoutTenantScope()->create([
            'league_id' => $league->id, 'name' => $name, 'game' => 'acc', 'season' => 2026,
            'status' => $status, 'visibility' => $visibility,
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function round(Championship $championship, string $track, string $status = 'open'): Race
    {
        return Race::create([
            'title' => $championship->name, 'track' => $track, 'game' => 'acc', 'status' => $status,
            'scheduled_at' => now()->addDays(3), 'championship_id' => $championship->id, 'round_number' => 2,
        ]);
    }

    public function test_the_championships_tab_lists_only_public_upcoming_rounds(): void
    {
        $public = $this->championship('Sunday Cup', 'registration_open');
        $this->round($public, 'Spa-Francorchamps');
        $this->round($public, 'Zolder', 'draft');
        $this->round($this->championship('Secret Cup', 'draft'), 'Imola');
        $this->round($this->championship('Hidden Cup', 'running', 'hidden'), 'Monza');

        $this->get('/')
            ->assertOk()
            ->assertSee('Sunday Cup')
            ->assertSee('ROUND 2 · Spa-Francorchamps')
            ->assertDontSee('Zolder')
            ->assertDontSee('Secret Cup')
            ->assertDontSee('Hidden Cup')
            ->assertDontSee('Season standings coming soon');
    }

    // The subtitle ("Season 3") tells two seasons of the same championship apart —
    // on the dashboard, the league's championship cards and the admin list.
    public function test_the_subtitle_shows_next_to_the_championship_name(): void
    {
        $championship = $this->championship('Sunday Cup', 'registration_open');
        $championship->update(['tagline' => 'Season 3']);
        $this->round($championship, 'Spa-Francorchamps');

        $this->get('/')->assertOk()->assertSee('SEASON 3 · ROUND 2 · Spa-Francorchamps');

        $this->get(route('championships.index', ['league' => 'nlrl']))
            ->assertOk()
            ->assertSee('Sunday Cup')
            ->assertSee('Season 3');

        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $championship->league_id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->actingAs($manager->refresh())
            ->get(route('admin.leagues.championships.index', $championship->league_id))
            ->assertOk()
            ->assertSee('Season 3');
    }
}
