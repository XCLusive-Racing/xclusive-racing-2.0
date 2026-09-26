<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\Role;
use App\Models\User;
use App\Services\FtpService;
use App\Services\PracticeServer\ChampionshipPracticeService;
use App\Settings\ChampionshipSettingsSchema;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

// 24h championship practice server: every midnight (UK) the server the next round
// runs on gets an open 24-hour practice session on that round's track.
class ChampionshipPracticeServerTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        // Midnight UK on the night before round day.
        $this->travelTo(Carbon::parse('2026-10-05 00:00', 'Europe/London'));

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeServer(array $overrides = []): FtpServer
    {
        return FtpServer::create(array_merge([
            'name' => 'NLRL Server 1', 'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'cfg_path' => '/cfg', 'server_type' => 'rolling', 'league_id' => $this->league->id,
            'game' => 'acc', 'platform' => 'console', 'active' => true,
        ], $overrides));
    }

    private function makeChampionship(bool $practice = true, ?FtpServer $server = null): Championship
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Sprint Series', 'game' => 'acc', 'season' => 2026,
            'status' => 'running', 'visibility' => 'public', 'ftp_server_id' => $server?->id,
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'sessions' => ['practice_server_enabled' => $practice],
        ]);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship, int $number, string $track, string $londonTime, ?FtpServer $server = null, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number, 'title' => 'Round '.$number,
            'track' => $track, 'game' => 'acc', 'status' => $status,
            'scheduled_at' => Carbon::parse($londonTime, 'Europe/London')->utc(), 'ftp_server_id' => $server?->id,
        ]);
    }

    private function service(): ChampionshipPracticeService
    {
        return app(ChampionshipPracticeService::class);
    }

    public function test_practice_is_the_next_rounds_track_and_moves_on_the_night_after_the_round(): void
    {
        $server = $this->makeServer();
        $championship = $this->makeChampionship(server: $server);
        $this->makeRound($championship, 1, 'monza', '2026-10-05 20:00');
        $this->makeRound($championship, 2, 'spa', '2026-10-12 20:00');

        // Midnight on round day: that day's track.
        $this->assertSame('monza', $this->service()->nextRound($championship)->track);

        // The midnight after the round: the next track, even before results are in.
        $this->travelTo(Carbon::parse('2026-10-06 00:00', 'Europe/London'));
        $this->assertSame('spa', $this->service()->nextRound($championship)->track);
    }

    public function test_the_rounds_own_server_wins_over_the_championship_default(): void
    {
        $default = $this->makeServer(['name' => 'Default']);
        $roundServer = $this->makeServer(['name' => 'Round Server']);
        $championship = $this->makeChampionship(server: $default);
        $round = $this->makeRound($championship, 1, 'monza', '2026-10-05 20:00', $roundServer);

        $this->assertSame('Round Server', $this->service()->serverFor($championship, $round)->name);

        $round->update(['ftp_server_id' => null]);
        $this->assertSame('Default', $this->service()->serverFor($championship, $round->fresh())->name);

        $default->update(['active' => false]);
        $this->assertNull($this->service()->serverFor($championship, $round->fresh()));
    }

    public function test_only_championships_with_practice_on_are_pushed_and_one_per_server(): void
    {
        $shared = $this->makeServer();
        $soon = $this->makeChampionship(server: $shared);
        $this->makeRound($soon, 1, 'monza', '2026-10-05 20:00');
        $later = $this->makeChampionship(server: $shared);
        $this->makeRound($later, 1, 'spa', '2026-10-09 20:00');
        $off = $this->makeChampionship(practice: false, server: $this->makeServer(['name' => 'Other']));
        $this->makeRound($off, 1, 'imola', '2026-10-05 20:00');

        $pushes = $this->service()->duePushes();

        $this->assertCount(1, $pushes);
        $this->assertSame($soon->id, $pushes[0]['championship']->id);
    }

    public function test_the_config_is_one_open_24_hour_practice_session(): void
    {
        $server = $this->makeServer();
        $round = $this->makeRound($this->makeChampionship(server: $server), 1, 'monza', '2026-10-05 20:00');
        $round->update(['pitstop_count' => 1]);

        $files = array_map(fn ($json) => json_decode($json, true), $this->service()->buildFiles($round->fresh(), $server));

        $this->assertSame('monza', $files['event.json']['track']);
        $this->assertCount(1, $files['event.json']['sessions']);
        $this->assertSame('P', $files['event.json']['sessions'][0]['sessionType']);
        $this->assertSame(1440, $files['event.json']['sessions'][0]['sessionDurationMinutes']);
        $this->assertSame(['entries' => [], 'forceEntryList' => 0], $files['entrylist.json']);
        $this->assertSame(0, $files['eventrules.json']['mandatoryPitstopCount']);
    }

    public function test_the_midnight_command_uploads_every_file_and_records_the_push(): void
    {
        $server = $this->makeServer();
        $championship = $this->makeChampionship(server: $server);
        $round = $this->makeRound($championship, 1, 'monza', '2026-10-05 20:00');

        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->once()->andReturn(true);
            foreach (['event', 'settings', 'eventrules', 'assistrules', 'entrylist'] as $file) {
                $ftp->shouldReceive('uploadConfigFile')->once()->with("/cfg/{$file}.json", \Mockery::type('string'))->andReturn(true);
            }
            $ftp->shouldReceive('disconnect')->once();
        });

        $this->artisan('championships:push-practice')->assertSuccessful();

        $championship->refresh();
        $this->assertNotNull($championship->practice_pushed_at);
        $this->assertSame($round->id, $championship->practice_race_id);
        $this->assertNull($championship->practice_push_error);
    }

    public function test_a_failed_push_is_recorded(): void
    {
        $championship = $this->makeChampionship(server: $this->makeServer());
        $this->makeRound($championship, 1, 'monza', '2026-10-05 20:00');

        $this->mock(FtpService::class, fn (MockInterface $ftp) => $ftp->shouldReceive('connect')->andReturn(false));

        $this->artisan('championships:push-practice')->assertSuccessful();

        $this->assertStringContainsString('Could not connect', $championship->fresh()->practice_push_error);
    }

    public function test_a_manager_can_push_practice_now(): void
    {
        $championship = $this->makeChampionship(server: $this->makeServer());
        $this->makeRound($championship, 1, 'monza', '2026-10-05 20:00');
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->andReturn(true);
            $ftp->shouldReceive('uploadConfigFile')->andReturn(true);
            $ftp->shouldReceive('disconnect');
        });

        $this->actingAs($manager->refresh())
            ->post(route('admin.leagues.championships.practice.push', [$this->league, $championship]))
            ->assertSessionHas('success', 'Practice for monza pushed to NLRL Server 1.');

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.wizard', [$this->league, $championship, 'sessions']))
            ->assertOk()->assertSee('24h Practice Server')->assertSee('monza (Round 1)')->assertSee('Push practice now');
    }

    public function test_push_now_without_an_upcoming_round_explains_why(): void
    {
        $championship = $this->makeChampionship(server: $this->makeServer());
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($admin)
            ->post(route('admin.leagues.championships.practice.push', [$this->league, $championship]))
            ->assertSessionHas('error', 'There is no upcoming round to practise for.');
    }
}
