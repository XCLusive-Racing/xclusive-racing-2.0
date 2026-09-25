<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ConnectedAccount;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ACC PC ('ac') and ACC Console ('acc') can't share servers or player IDs, so a
// championship's game decides its platform, its rounds' game, which servers its
// rounds may use, and — for whole-championship team entries — that every team
// member has a Steam ID.
class ChampionshipAccPcTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager->refresh();
    }

    private function makeChampionship(array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    private function makeServer(string $platform): FtpServer
    {
        return FtpServer::create([
            'name' => ucfirst($platform).' Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'server_number' => 1,
            'league_id' => $this->league->id, 'game' => 'acc', 'platform' => $platform,
            'active' => true,
        ]);
    }

    private function makeRound(Championship $championship, ?FtpServer $server = null): Race
    {
        return Race::create([
            'title' => 'Round', 'track' => 'monza', 'game' => $championship->game, 'status' => 'open',
            'scheduled_at' => now()->addWeek()->startOfHour(), 'championship_id' => $championship->id,
            'ftp_server_id' => $server?->id, 'slot_time' => $server ? now()->addWeek()->startOfHour() : null,
            'config_push_status' => $server ? 'pending' : null,
        ]);
    }

    private function saveBasics(Championship $championship, array $overrides = [])
    {
        return $this->actingAs($this->manager)->put(
            route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'basics']),
            array_merge([
                'name' => 'Test Cup', 'slug' => 'test-cup-'.$championship->id,
                'game' => 'ac', 'platform' => 'pc', 'visibility' => 'public',
                'settings' => ['schedule' => ['recurrence' => 'weekly', 'time_of_day' => '14:00']],
            ], $overrides)
        );
    }

    public function test_acc_platform_follows_the_game(): void
    {
        $championship = $this->makeChampionship();

        $this->saveBasics($championship, ['game' => 'ac', 'platform' => 'console'])->assertSessionHasNoErrors();
        $this->assertSame('pc', $championship->fresh()->platform);

        $this->saveBasics($championship, ['game' => 'acc', 'platform' => 'pc'])->assertSessionHasNoErrors();
        $this->assertSame('console', $championship->fresh()->platform);

        $this->saveBasics($championship, ['game' => 'acc', 'platform' => 'cross'])->assertSessionHasNoErrors();
        $this->assertSame('cross', $championship->fresh()->platform);
    }

    public function test_default_server_must_match_the_games_platform(): void
    {
        $championship = $this->makeChampionship();
        $console = $this->makeServer('console');

        $this->saveBasics($championship, ['ftp_server_id' => $console->id])
            ->assertSessionHasErrors(['ftp_server_id' => FtpServer::ERR_WRONG_PLATFORM]);
        $this->assertSame('acc', $championship->fresh()->game);
    }

    public function test_changing_the_game_moves_the_rounds_and_drops_servers_of_the_other_platform(): void
    {
        $championship = $this->makeChampionship();
        $onConsole = $this->makeRound($championship, $this->makeServer('console'));
        $withoutServer = $this->makeRound($championship);

        $this->saveBasics($championship)->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 round(s)'));

        $onConsole->refresh();
        $this->assertSame('ac', $onConsole->game);
        $this->assertNull($onConsole->ftp_server_id);
        $this->assertNull($onConsole->slot_time);
        $this->assertNull($onConsole->config_push_status);
        $this->assertSame('ac', $withoutServer->fresh()->game);
    }

    public function test_the_game_is_locked_once_a_round_has_results(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship);
        RaceResult::create([
            'race_id' => $round->id, 'session_type' => 'race', 'driver_name' => 'Driver',
            'position' => 1, 'lap_count' => 20, 'total_time' => 1_200_000,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);

        $this->saveBasics($championship)->assertSessionHasErrors(['game' => Championship::ERR_GAME_LOCKED]);
        $this->assertSame('acc', $championship->fresh()->game);
        $this->assertSame('acc', $round->fresh()->game);
    }

    public function test_round_forms_only_offer_servers_of_the_championships_platform(): void
    {
        $championship = $this->makeChampionship(['game' => 'ac', 'platform' => 'pc']);
        $this->makeServer('console');
        $pc = $this->makeServer('pc');

        $this->actingAs($this->manager)
            ->get(route('admin.leagues.championships.rounds.create', [$this->league, $championship]))
            ->assertOk()
            ->assertViewHas('servers', fn ($servers) => $servers->pluck('id')->all() === [$pc->id]);
    }

    public function test_whole_championship_team_entry_needs_steam_for_every_member_on_acc_pc(): void
    {
        $championship = $this->makeChampionship([
            'game' => 'ac', 'platform' => 'pc', 'status' => 'registration_open', 'registration_open' => true,
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['driver_swaps_enabled' => true, 'team_registration_scope' => 'championship'],
        ]);
        $championship->save();

        $owner = User::factory()->create(['platform' => 'steam', 'platform_id' => 'S76561190000000001']);
        $member = User::factory()->create(['platform' => 'xbox', 'platform_id' => 'M2535400000000001']);
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach($member->id);

        $register = fn () => $this->actingAs($owner)->post(route('championships.register', $championship), [
            'racing_team_id' => $team->id, 'car_number' => 7, 'starting_driver_id' => $owner->id,
        ]);

        $register()->assertSessionHas('error', $member->displayName().': '.User::STEAM_REQUIRED_MESSAGE);
        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id]);

        ConnectedAccount::create([
            'user_id' => $member->id, 'provider' => 'steam', 'provider_id' => 'S76561190000000002',
            'username' => 'member', 'connected_at' => now(),
        ]);

        $register()->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertDatabaseHas('championship_registrations', [
            'championship_id' => $championship->id, 'racing_team_id' => $team->id,
        ]);
    }
}
