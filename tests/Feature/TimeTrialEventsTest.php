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

    public function test_the_upload_is_due_once_an_hour_for_the_live_event(): void
    {
        $live = $this->event();
        $this->event(['starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(14)]); // next week
        $servers = app(TimeTrialServerService::class);

        $this->assertSame([$live->id], $servers->duePushes(now())->pluck('id')->all());

        $live->update(['last_pushed_at' => now()->subMinutes(20)]);
        $this->assertCount(0, $servers->duePushes(now()), 'Uploaded 20 minutes ago');

        $live->update(['last_pushed_at' => now()->subMinutes(60)]);
        $this->assertCount(1, $servers->duePushes(now()), 'An hour later it goes up again');
    }

    public function test_between_events_the_next_one_is_uploaded(): void
    {
        $next = $this->event(['starts_at' => now()->addHours(3), 'ends_at' => now()->addDays(7)]);

        $this->assertSame([$next->id], app(TimeTrialServerService::class)->duePushes(now())->pluck('id')->all());
    }

    public function test_the_config_is_practice_then_qualifying_with_an_open_entry_list_of_all_members(): void
    {
        $event = $this->event();
        $this->driver('M1', 'Alpha');
        $this->driver('P2', 'Bravo');
        User::factory()->create(['platform_id' => 'M3', 'is_filler' => true]);
        User::factory()->create(['platform_id' => 'S7656', 'platform' => 'steam']);

        [$files, $entryCount] = app(TimeTrialServerService::class)->buildFiles($event, $this->server);

        $config = json_decode($files['event.json'], true);
        $this->assertSame('spa', $config['track']);
        $this->assertSame([['P', 2], ['Q', 30]], array_map(fn ($s) => [$s['sessionType'], $s['sessionDurationMinutes']], $config['sessions']));

        $settings = json_decode($files['settings.json'], true);
        $this->assertSame('GT3', $settings['carGroup']);
        $this->assertSame('XCL SERVER 6 - Time Trials', $settings['serverName']);

        // Every console member, signed up or not; no grid fillers, no Steam-only accounts.
        $entryList = json_decode($files['entrylist.json'], true);
        $this->assertSame(0, $entryList['forceEntryList']);
        $this->assertSame(2, $entryCount);
        $this->assertSame(['M1', 'P2'], array_map(fn ($e) => $e['drivers'][0]['playerID'], $entryList['entries']));
    }

    public function test_a_forced_entry_list_holds_only_the_signups(): void
    {
        $event = $this->event(['forced_entry_list' => true]);
        $event->drivers()->attach($this->driver('M1', 'Alpha'));
        $this->driver('P2', 'Not Signed Up');

        [$files, $entryCount] = app(TimeTrialServerService::class)->buildFiles($event, $this->server);

        $entryList = json_decode($files['entrylist.json'], true);
        $this->assertSame(1, $entryList['forceEntryList']);
        $this->assertSame(1, $entryCount);
        $this->assertSame('M1', $entryList['entries'][0]['drivers'][0]['playerID']);
    }

    public function test_a_signup_triggers_a_new_upload_only_with_a_forced_entry_list(): void
    {
        $forced = $this->event(['forced_entry_list' => true, 'last_pushed_at' => now()->subMinutes(10)]);
        $user = $this->driver('M1');

        $this->actingAs($user)->post(route('time-trials.events.register', $forced));
        $this->assertNull($forced->fresh()->last_pushed_at);
        $this->assertCount(1, app(TimeTrialServerService::class)->duePushes(now()));

        $open = $this->event([
            'track' => 'monza', 'starts_at' => now()->addDays(7), 'ends_at' => now()->addDays(14),
            'last_pushed_at' => now()->subMinutes(10),
        ]);
        $this->actingAs($user)->post(route('time-trials.events.register', $open));
        $this->assertNotNull($open->fresh()->last_pushed_at);
    }

    public function test_an_upload_sends_every_file_and_records_it(): void
    {
        $event = $this->event();
        $this->mock(FtpService::class, function (MockInterface $ftp) {
            $ftp->shouldReceive('connect')->once()->andReturn(true);
            $ftp->shouldReceive('uploadConfigFile')->times(5)->andReturn(true);
            $ftp->shouldReceive('renameFile')->times(5)->andReturn(true);
            $ftp->shouldReceive('disconnect')->once();
        });

        $this->assertNull(app(TimeTrialServerService::class)->push($event));
        $this->assertNotNull($event->fresh()->last_pushed_at);
        $this->assertNull($event->fresh()->last_push_error);
    }

    // ── Results ─────────────────────────────────────────────────────────────────

    private function resultFile(array $laps, string $track = 'spa', string $type = 'FP'): string
    {
        return json_encode([
            'sessionType' => $type,
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

    public function test_qualifying_laps_count_too(): void
    {
        $event = $this->event();
        $event->drivers()->attach($this->driver('M1'));

        $json = $this->resultFile([
            ['carId' => 1001, 'driverIndex' => 0, 'laptime' => 136000, 'isValidForBest' => true, 'splits' => [40000, 50000, 46000]],
        ], type: 'Q');

        $this->assertSame(1, app(TimeTrialResultCollector::class)->storeFile($event, $this->server->id, '261005_1432_Q.json', $json));
    }

    public function test_sessions_that_ended_before_the_driver_signed_up_do_not_count(): void
    {
        $event = $this->event();
        $alpha = $this->driver('M1');
        $event->drivers()->attach($alpha, ['created_at' => Carbon::parse('2026-10-05 15:00', 'Europe/Berlin')->utc()]);

        $lap = [['carId' => 1001, 'driverIndex' => 0, 'laptime' => 136000, 'isValidForBest' => true, 'splits' => [40000, 50000, 46000]]];
        $collector = app(TimeTrialResultCollector::class);

        $this->assertSame(0, $collector->storeFile($event, $this->server->id, '261005_1432_Q.json', $this->resultFile($lap, type: 'Q')));
        $this->assertSame(1, $collector->storeFile($event, $this->server->id, '261005_1532_Q.json', $this->resultFile($lap, type: 'Q')));
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
