<?php

namespace Tests\Feature;

use App\Models\FtpServer;
use App\Models\League;
use App\Models\Role;
use App\Models\TimeTrialCar;
use App\Models\TimeTrialEvent;
use App\Models\TimeTrialEventLap;
use App\Models\TimeTrialLap;
use App\Models\User;
use App\Services\FtpService;
use App\Services\TimeTrials\TimeTrialFinalizer;
use App\Services\TimeTrials\TimeTrialRating;
use App\Services\TimeTrials\TimeTrialResultCollector;
use App\Services\TimeTrials\TimeTrialServerService;
use App\Services\TimeTrials\TimeTrialStandings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class TimeTrialEventsTest extends TestCase
{
    use RefreshDatabase;

    private FtpServer $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = FtpServer::create([
            'name' => 'XCL SERVER 6', 'ingame_name' => 'XCL SERVER 6 - Time Trials', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results', 'cfg_path' => '/cfg',
            'server_type' => 'rolling', 'reset_start_hour' => 0, 'reset_interval_minutes' => 60,
            'server_number' => 6, 'active' => true,
            'league_id' => League::system()->id, 'game' => 'acc', 'platform' => 'console',
        ]);

        TimeTrialCar::create(['id' => 32, 'name' => 'Ferrari 296 GT3', 'year' => 2023, 'acc_car_model' => 32]);
        TimeTrialCar::create(['id' => 35, 'name' => 'McLaren 720S GT3 Evo', 'year' => 2023, 'acc_car_model' => 35]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(array $overrides = []): TimeTrialEvent
    {
        return TimeTrialEvent::create(array_merge([
            'track' => 'spa', 'car_class' => 'GT3',
            'starts_at' => now()->subDay()->startOfHour(), 'ends_at' => now()->addDays(6)->startOfHour(),
            'ftp_server_id' => $this->server->id, 'is_published' => true,
        ], $overrides));
    }

    private function driver(string $playerId, string $name = 'Driver'): User
    {
        return User::factory()->create(['name' => $name, 'platform' => 'xbox', 'platform_id' => $playerId]);
    }

    private function eventLap(TimeTrialEvent $event, User $user, int $carId, int $lapMs, string $file = '260101_1000_FP.json'): TimeTrialEventLap
    {
        return TimeTrialEventLap::create([
            'time_trial_event_id' => $event->id, 'user_id' => $user->id, 'platform_identifier' => $user->platform_id,
            'platform' => 'xbox', 'driver_name' => $user->name, 'car_id' => $carId, 'car_class' => 'GT3',
            'lap_time_ms' => $lapMs, 'sector1_ms' => 40000, 'sector2_ms' => 50000, 'sector3_ms' => $lapMs - 90000,
            'result_file' => $file, 'recorded_at' => now(),
        ]);
    }

    private function allTimeLap(User $user, int $carId, int $lapMs, string $track = 'spa'): TimeTrialLap
    {
        return TimeTrialLap::create([
            'user_id' => $user->id, 'platform_identifier' => $user->platform_id, 'platform' => 'xbox',
            'driver_name' => $user->name, 'track' => $track, 'car_id' => $carId, 'car_class' => 'GT3',
            'lap_time_ms' => $lapMs, 'source' => 'import', 'is_personal_best' => true,
            'source_key' => sha1("test|{$user->id}|{$carId}|{$lapMs}"),
        ]);
    }

    // ── Rating ──────────────────────────────────────────────────────────────────

    public function test_points_run_from_50_for_the_winner_to_1_for_the_last(): void
    {
        $this->assertSame(50, TimeTrialRating::points(1, 10));
        $this->assertSame(1, TimeTrialRating::points(10, 10));
        $this->assertSame(26, TimeTrialRating::points(2, 3));
        $this->assertSame(50, TimeTrialRating::points(1, 1));
    }

    // ── Standings ───────────────────────────────────────────────────────────────

    public function test_a_lap_only_counts_when_it_beats_the_drivers_own_all_time_record_in_that_car(): void
    {
        $event = $this->event();
        $alpha = $this->driver('M1', 'Alpha');
        $bravo = $this->driver('M2', 'Bravo');

        $this->allTimeLap($alpha, 35, 135000);           // Alpha's record in the McLaren
        $this->eventLap($event, $alpha, 35, 136000);      // slower than that: doesn't count
        $this->eventLap($event, $alpha, 32, 137000);      // another car: counts
        $this->eventLap($event, $bravo, 35, 136500);      // no record: counts

        $rows = app(TimeTrialStandings::class)->for($event);

        $this->assertSame(['Bravo', 'Alpha'], $rows->map(fn ($r) => $r['lap']->driver_name)->all());
        $this->assertSame(137000, $rows[1]['lap']->lap_time_ms);
        $this->assertSame([50, 1], $rows->pluck('points')->all());
    }

    public function test_a_faster_lap_replaces_the_older_one(): void
    {
        $event = $this->event();
        $alpha = $this->driver('M1', 'Alpha');
        $this->eventLap($event, $alpha, 35, 136000, '260101_1000_FP.json');
        $this->eventLap($event, $alpha, 35, 135500, '260101_1100_FP.json');

        $rows = app(TimeTrialStandings::class)->for($event);

        $this->assertCount(1, $rows);
        $this->assertSame(135500, $rows[0]['lap']->lap_time_ms);
    }

    // ── Server push ─────────────────────────────────────────────────────────────

    public function test_next_restart_is_the_next_full_hour_on_an_hourly_server(): void
    {
        $next = app(TimeTrialServerService::class)->nextRestart($this->server, Carbon::parse('2026-10-05 13:42', 'Europe/London'));

        $this->assertSame('2026-10-05 14:00', $next->timezone('Europe/London')->format('Y-m-d H:i'));
    }

    public function test_the_push_is_due_ten_minutes_before_a_restart_inside_the_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 13:52', 'Europe/London'));
        $event = $this->event([
            'starts_at' => Carbon::parse('2026-10-05 00:00', 'Europe/London'),
            'ends_at' => Carbon::parse('2026-10-12 00:00', 'Europe/London'),
        ]);
        $servers = app(TimeTrialServerService::class);

        $this->assertCount(1, $servers->duePushes(now()));

        $event->update(['last_pushed_for' => Carbon::parse('2026-10-05 14:00', 'Europe/London')]);
        $this->assertCount(0, $servers->duePushes(now()), 'Already pushed for this restart');

        Carbon::setTestNow(Carbon::parse('2026-10-05 14:30', 'Europe/London'));
        $this->assertCount(0, $servers->duePushes(now()), 'Too early for the 15:00 restart');
    }

    public function test_the_config_is_one_practice_session_with_a_forced_entry_list_of_signups(): void
    {
        $event = $this->event();
        $signedUp = $this->driver('M1', 'Alpha');
        $this->driver('M2', 'Not Signed Up');
        $event->drivers()->attach($signedUp);

        [$files, $entryCount] = app(TimeTrialServerService::class)->buildFiles($event, $this->server);

        $config = json_decode($files['event.json'], true);
        $this->assertSame('spa', $config['track']);
        $this->assertCount(1, $config['sessions']);
        $this->assertSame('P', $config['sessions'][0]['sessionType']);
        $this->assertSame(55, $config['sessions'][0]['sessionDurationMinutes']);

        $settings = json_decode($files['settings.json'], true);
        $this->assertSame('GT3', $settings['carGroup']);
        $this->assertSame('XCL SERVER 6 - Time Trials', $settings['serverName']);

        $entryList = json_decode($files['entrylist.json'], true);
        $this->assertSame(1, $entryList['forceEntryList']);
        $this->assertSame(1, $entryCount);
        $this->assertSame('M1', $entryList['entries'][0]['drivers'][0]['playerID']);
    }

    public function test_a_push_uploads_every_file_and_records_the_restart(): void
    {
        $event = $this->event();
        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->once()->andReturn(true);
            $ftp->shouldReceive('uploadConfigFile')->times(5)->andReturn(true);
            $ftp->shouldReceive('renameFile')->times(5)->andReturn(true);
            $ftp->shouldReceive('disconnect')->once();
        });

        $servers = app(TimeTrialServerService::class);
        $restart = $servers->nextRestart($this->server, now());

        $this->assertNull($servers->push($event, $restart));
        $this->assertTrue($event->fresh()->last_pushed_for->eq($restart));
    }

    // ── Results ─────────────────────────────────────────────────────────────────

    private function resultFile(array $laps, string $track = 'spa'): string
    {
        return json_encode([
            'sessionType' => 'FP',
            'trackName' => $track,
            'sessionResult' => ['leaderBoardLines' => [
                ['car' => ['carId' => 1001, 'carModel' => 35, 'drivers' => [['playerId' => 'M1', 'firstName' => 'A', 'lastName' => 'B']]]],
                ['car' => ['carId' => 1002, 'carModel' => 32, 'drivers' => [['playerId' => 'M9', 'firstName' => 'X', 'lastName' => 'Y']]]],
            ]],
            'laps' => $laps,
        ]);
    }

    public function test_results_keep_each_signed_up_drivers_best_valid_lap_per_car(): void
    {
        $event = $this->event();
        $alpha = $this->driver('M1', 'Alpha');
        $event->drivers()->attach($alpha);

        $json = $this->resultFile([
            ['carId' => 1001, 'driverIndex' => 0, 'laptime' => 136000, 'isValidForBest' => true, 'splits' => [40000, 50000, 46000]],
            ['carId' => 1001, 'driverIndex' => 0, 'laptime' => 135000, 'isValidForBest' => false, 'splits' => [40000, 50000, 45000]],
            ['carId' => 1001, 'driverIndex' => 0, 'laptime' => 135800, 'isValidForBest' => true, 'splits' => [40000, 50000, 45800]],
            ['carId' => 1002, 'driverIndex' => 0, 'laptime' => 130000, 'isValidForBest' => true, 'splits' => [40000, 45000, 45000]], // not signed up
        ]);

        $collector = app(TimeTrialResultCollector::class);
        $this->assertSame(1, $collector->storeFile($event, $this->server->id, '261005_1355_FP.json', $json));

        $lap = TimeTrialEventLap::sole();
        $this->assertSame(135800, $lap->lap_time_ms);
        $this->assertSame([40000, 50000, 45800], [$lap->sector1_ms, $lap->sector2_ms, $lap->sector3_ms]);
        $this->assertSame($alpha->id, $lap->user_id);
        $this->assertSame(35, $lap->car_id);
    }

    public function test_a_result_file_from_another_track_is_ignored(): void
    {
        $event = $this->event();
        $event->drivers()->attach($this->driver('M1'));

        $json = $this->resultFile([
            ['carId' => 1001, 'driverIndex' => 0, 'laptime' => 136000, 'isValidForBest' => true, 'splits' => [1, 2, 3]],
        ], 'monza');

        $this->assertSame(0, app(TimeTrialResultCollector::class)->storeFile($event, $this->server->id, '261005_1355_FP.json', $json));
    }

    // ── Finalize ────────────────────────────────────────────────────────────────

    public function test_finalizing_stores_results_awards_points_and_adds_laps_to_the_records(): void
    {
        $event = $this->event(['starts_at' => now()->subDays(8), 'ends_at' => now()->subDay()]);
        $alpha = $this->driver('M1', 'Alpha');
        $bravo = $this->driver('M2', 'Bravo');
        $alpha->update(['elo_acc' => 3000]);
        $bravo->update(['elo_acc' => 2000]);

        $this->eventLap($event, $alpha, 35, 135000);
        $this->eventLap($event, $alpha, 32, 136000);
        $this->eventLap($event, $bravo, 35, 135500);

        $finalizer = app(TimeTrialFinalizer::class);
        $this->assertTrue($finalizer->isDue($event));
        $this->assertTrue($finalizer->finalize($event));
        $this->assertFalse($finalizer->finalize($event->fresh()), 'Runs only once');

        $this->assertSame([1, 2], $event->results()->pluck('position')->all());
        $this->assertSame(3050, $alpha->fresh()->elo_acc);
        $this->assertSame(2001, $bravo->fresh()->elo_acc);

        // Each driver's best per car joins the All Time Records as the new personal best.
        $merged = TimeTrialLap::where('time_trial_event_id', $event->id)->get();
        $this->assertCount(3, $merged);
        $this->assertTrue($merged->every(fn ($lap) => $lap->source === 'server' && $lap->is_personal_best));

        $this->get(route('time-trials.show', 'spa'))->assertOk()->assertSeeInOrder(['Alpha', 'Bravo']);
    }

    // ── Pages ───────────────────────────────────────────────────────────────────

    public function test_a_driver_can_sign_up_and_withdraw(): void
    {
        $event = $this->event();
        $user = $this->driver('M1');

        $this->actingAs($user)->post(route('time-trials.events.register', $event))->assertRedirect();
        $this->assertTrue($event->isRegistered($user));

        $this->actingAs($user)->get(route('time-trials.events.show', $event))
            ->assertOk()->assertSee('You are signed up')->assertSee('XCL SERVER 6 - Time Trials');

        $this->actingAs($user)->delete(route('time-trials.events.withdraw', $event))->assertRedirect();
        $this->assertFalse($event->isRegistered($user));
    }

    public function test_signup_needs_a_console_account(): void
    {
        $event = $this->event();
        $user = User::factory()->create(['platform' => null, 'platform_id' => null]);

        $this->actingAs($user)->post(route('time-trials.events.register', $event))->assertSessionHas('error');
        $this->assertFalse($event->isRegistered($user));
    }

    public function test_time_trials_page_shows_this_weeks_event(): void
    {
        $this->event(['title' => 'Spa Week']);

        $this->get(route('time-trials.index'))->assertOk()->assertSee('Spa Week')->assertSee('All Time Records');
    }

    public function test_an_unpublished_event_is_hidden(): void
    {
        $event = $this->event(['is_published' => false]);

        $this->get(route('time-trials.events.show', $event))->assertNotFound();
    }

    public function test_admin_can_create_an_event_but_not_one_that_overlaps(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $payload = [
            'track' => 'monza', 'car_class' => 'GT3', 'ftp_server_id' => $this->server->id, 'is_published' => '1',
            'starts_at' => '2026-10-05T00:00', 'ends_at' => '2026-10-12T00:00',
        ];

        $this->actingAs($admin)->get(route('admin.time-trials.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.time-trials.store'), $payload)->assertRedirect(route('admin.time-trials.index'));
        $this->assertSame('2026-10-04 23:00', TimeTrialEvent::sole()->starts_at->format('Y-m-d H:i'), 'UK time stored as UTC');

        $this->actingAs($admin)->post(route('admin.time-trials.store'), ['starts_at' => '2026-10-08T00:00', 'ends_at' => '2026-10-15T00:00'] + $payload)
            ->assertSessionHasErrors('starts_at');

        $this->actingAs($admin)->get(route('admin.time-trials.index'))->assertOk()->assertSee('Monza Time Trial');
    }
}
