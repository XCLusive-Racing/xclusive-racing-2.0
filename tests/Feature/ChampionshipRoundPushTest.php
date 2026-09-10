<?php

namespace Tests\Feature;

use App\Jobs\PushRoundConfigJob;
use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

// Phase 3 (docs/championships/PLAN.md): a league manager pushing their own round's
// config now queues PushRoundConfigJob rather than blocking the request on an FTP
// round-trip — these tests fake the queue rather than hitting a real FTP server.
class ChampionshipRoundPushTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function attachManager(User $user, League $league): void
    {
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $user->id, 'role' => 'manager']);
        $user->syncLeagueRoleFlags();
        $user->refresh();
    }

    private function makeChampionshipWithRound(League $league, ?FtpServer $server = null): array
    {
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);

        $race = Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Test Cup — Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'is_championship' => true,
            'event_tag' => 'championship', 'scheduled_at' => now()->addDay(),
            'ftp_server_id' => $server?->id,
        ]);

        return [$championship, $race];
    }

    public function test_rounds_wizard_step_renders_the_push_config_button(): void
    {
        $league = $this->makeLeague('nlrl');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        [$championship, $race] = $this->makeChampionshipWithRound($league, $server);

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$league, $championship, 'rounds']))
            ->assertOk()
            ->assertSee('Push Config');
    }

    public function test_league_manager_can_queue_a_push_for_their_own_round(): void
    {
        Bus::fake();

        $league = $this->makeLeague('nlrl');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        [$championship, $race] = $this->makeChampionshipWithRound($league, $server);

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.push-config', [$league, $championship, $race]))
            ->assertRedirect();

        Bus::assertDispatched(PushRoundConfigJob::class, fn ($job) => $job->raceId === $race->id);
        $this->assertSame('pending', $race->fresh()->config_push_status);
    }

    public function test_pushing_a_round_with_no_assigned_server_is_rejected(): void
    {
        Bus::fake();

        $league = $this->makeLeague('nlrl');
        [$championship, $race] = $this->makeChampionshipWithRound($league);

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.push-config', [$league, $championship, $race]))
            ->assertNotFound();

        Bus::assertNotDispatched(PushRoundConfigJob::class);
    }

    public function test_league_manager_cannot_push_another_leagues_round(): void
    {
        Bus::fake();

        $nlrl = $this->makeLeague('nlrl');
        $src  = $this->makeLeague('src');

        $server = FtpServer::create([
            'name' => 'SRC Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $src->id,
        ]);
        [$championship, $race] = $this->makeChampionshipWithRound($src, $server);

        $manager = User::factory()->leagueManager()->create();
        $this->attachManager($manager, $nlrl);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.push-config', [$src, $championship, $race]))
            ->assertNotFound();

        Bus::assertNotDispatched(PushRoundConfigJob::class);
    }
}
