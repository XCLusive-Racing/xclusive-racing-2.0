<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request: settings.sessions.track_temp/cloud_level existed since Phase 2
// with help text saying "only used when weather is fixed," but nothing anywhere ever
// read them. No round-level UI exists for raw track/cloud values (same as the
// reference "dailies" race form never exposing them), so they're wired in as a
// championship-wide fallback layer in AccServerConfigService::configuration()
// instead of a new per-round field.
class ChampionshipFixedWeatherDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(League $league, array $sessionOverrides = []): Championship
    {
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'sessions' => $sessionOverrides,
        ]);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship, array $overrides = []): Race
    {
        return Race::create(array_merge([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ], $overrides));
    }

    public function test_a_league_championships_fixed_weather_settings_flow_into_the_pushed_config(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, [
            'weather_mode' => 'fixed', 'ambient_temp' => 18, 'track_temp' => 24, 'cloud_level' => 0.4, 'rain_level' => 0.15,
        ]);
        $round = $this->makeRound($championship); // no per-round weather override

        $config = app(AccServerConfigService::class)->configuration($round);

        $this->assertSame(18, $config['ambientTemp']);
        $this->assertSame(24, $config['trackTemp']);
        $this->assertSame(0.4, $config['cloudLevel']);
        $this->assertSame(0.15, $config['rain']);
    }

    public function test_a_round_with_its_own_ambient_temp_overrides_the_championships_fixed_default(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['weather_mode' => 'fixed', 'ambient_temp' => 18, 'track_temp' => 24]);
        $round = $this->makeRound($championship, ['ambient_temp' => 30]);

        $config = app(AccServerConfigService::class)->configuration($round);

        $this->assertSame(30, $config['ambientTemp']);
        // The round has no track_temp field of its own — the championship's
        // fixed default still applies for it.
        $this->assertSame(24, $config['trackTemp']);
    }

    public function test_randomised_weather_mode_does_not_apply_the_fixed_defaults(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['weather_mode' => 'randomised', 'track_temp' => 24, 'cloud_level' => 0.4]);
        $round = $this->makeRound($championship);

        $config = app(AccServerConfigService::class)->configuration($round);

        // Falls through to the built-in default (-1), not the unused fixed value.
        $this->assertSame(-1, $config['trackTemp']);
    }

    public function test_xcls_native_championship_is_unaffected(): void
    {
        $xcl = League::system();
        $championship = $this->makeChampionship($xcl, ['weather_mode' => 'fixed', 'track_temp' => 24]);
        $round = $this->makeRound($championship);

        $config = app(AccServerConfigService::class)->configuration($round);

        $this->assertSame(-1, $config['trackTemp']);
    }

    public function test_a_round_with_no_championship_at_all_is_unaffected(): void
    {
        $round = Race::create([
            'title' => 'Standalone', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);

        $config = app(AccServerConfigService::class)->configuration($round);

        $this->assertSame(-1, $config['trackTemp']);
    }
}
