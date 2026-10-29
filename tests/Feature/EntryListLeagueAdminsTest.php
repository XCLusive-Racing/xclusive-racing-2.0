<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Services\PracticeServer\ChampionshipPracticeService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature request 2026-10-25 (like SimGrid): the league's managers are on every
// entrylist as server admins, so they can join in any car for testing.
class EntryListLeagueAdminsTest extends TestCase
{
    use RefreshDatabase;

    private function leagueWithManagers(string $slug, array $playerIds): League
    {
        $league = League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        foreach ($playerIds as $playerId) {
            $manager = User::factory()->create(['platform_id' => $playerId]);
            LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        }

        return $league;
    }

    private function roundOf(League $league): Race
    {
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'active', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);

        return Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
    }

    public function test_league_managers_are_added_as_bare_server_admin_entries(): void
    {
        $league = $this->leagueWithManagers('nlrl', ['M-ADMIN-1', 'M-ADMIN-2']);
        $race = $this->roundOf($league);
        $this->leagueWithManagers('other', ['M-OTHER-LEAGUE']);
        LeagueUser::create([
            'league_id' => $league->id,
            'user_id' => User::factory()->create(['platform_id' => 'M-STEWARD'])->id,
            'role' => 'steward',
        ]);
        $driver = User::factory()->create(['platform_id' => 'M-DRIVER']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driver->id]);

        $entries = collect(app(AccServerConfigService::class)->entryList($race)['entries']);

        $admins = $entries->where('isServerAdmin', 1)->values();
        $this->assertSame([
            ['drivers' => [['playerID' => 'M-ADMIN-1']], 'isServerAdmin' => 1],
            ['drivers' => [['playerID' => 'M-ADMIN-2']], 'isServerAdmin' => 1],
        ], $admins->all());
        $this->assertCount(3, $entries);
        $this->assertArrayNotHasKey('isServerAdmin', $entries->first());
    }

    public function test_a_manager_who_races_gets_the_flag_on_their_own_car(): void
    {
        $race = $this->roundOf($this->leagueWithManagers('nlrl', ['M-ADMIN-1']));
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => User::where('platform_id', 'M-ADMIN-1')->value('id')]);

        $entries = app(AccServerConfigService::class)->entryList($race)['entries'];

        $this->assertCount(1, $entries);
        $this->assertSame(1, $entries[0]['isServerAdmin']);
        $this->assertArrayHasKey('raceNumber', $entries[0]);
    }

    public function test_a_manager_without_a_player_id_is_skipped(): void
    {
        $race = $this->roundOf($this->leagueWithManagers('nlrl', [null]));

        $this->assertSame([], app(AccServerConfigService::class)->entryList($race)['entries']);
    }

    // User-directed 2026-10-29: the championship's 24h practice server lists the
    // league's managers/admins as server admins too, not only its entrants.
    public function test_championship_practice_entrylist_lists_league_managers_as_server_admins(): void
    {
        $race = $this->roundOf($this->leagueWithManagers('nlrl', ['M-ADMIN-1']));

        $entries = app(ChampionshipPracticeService::class)->entryList($race)['entries'];

        $this->assertSame([['drivers' => [['playerID' => 'M-ADMIN-1']], 'isServerAdmin' => 1]], $entries);
    }
}
