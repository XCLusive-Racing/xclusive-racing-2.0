<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A championship round without its own icon used to fall back to XCL's generic
// "special event" logo — it should show its championship's icon, else its league's logo.
class RaceIconTest extends TestCase
{
    use RefreshDatabase;

    private function makeRound(?string $championshipIcon): Race
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl', 'logo' => 'leagues/nlrl-logo.png',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $championship = Championship::withoutTenantScope()->create([
            'league_id' => $league->id, 'name' => 'Cup', 'game' => 'acc', 'season' => 2026, 'icon' => $championshipIcon,
            'status' => 'active', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);

        return Race::create([
            'title' => 'Cup — Round 1', 'game' => 'acc', 'track' => 'Monza', 'scheduled_at' => now()->addWeek(),
            'status' => 'open', 'championship_id' => $championship->id, 'is_championship' => true,
        ]);
    }

    public function test_a_round_without_icons_of_its_own_uses_the_league_logo_for_anonymous_visitors(): void
    {
        $race = $this->makeRound(null);

        $this->assertStringEndsWith('leagues/nlrl-logo.png', Race::withIconOwners()->find($race->id)->icon_url);
    }

    public function test_the_championship_icon_wins_over_the_league_logo(): void
    {
        $race = $this->makeRound('championships/cup-icon.png');

        $this->assertStringEndsWith('championships/cup-icon.png', Race::find($race->id)->icon_url);
    }
}
