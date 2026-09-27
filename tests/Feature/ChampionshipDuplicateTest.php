<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A new season from an existing championship: settings, classes and (optionally)
// the rounds, shifted to a new start date; entries and results start over.
class ChampionshipDuplicateTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-01 12:00', 'Europe/London'));

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager->refresh();

        $this->championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Sunday League', 'game' => 'acc', 'season' => 2026,
            'status' => 'completed', 'visibility' => 'public', 'is_multiclass' => true, 'xcl_rating_enabled' => true,
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $this->championship->settings = array_replace_recursive($this->championship->settings->toArray(), [
            'balance' => [
                'success_ballast_enabled' => true, 'success_ballast_kg' => '5, 3, 0, -2',
                'adjustments' => [['scope' => 'driver', 'target' => 'Someone', 'ballast_kg' => 10, 'restrictor_percent' => 0]],
            ],
            'requirements' => ['registration_opens_at' => '2026-08-01T12:00'],
        ]);
        $this->championship->save();
        $this->championship->classes()->create(['name' => 'GT3', 'car_class' => 'GT3', 'color' => '#7c3aed', 'max_drivers' => 20, 'sort_order' => 0]);
        $this->championship->classes()->create(['name' => 'GT4', 'car_class' => 'GT4', 'color' => '#db2777', 'max_drivers' => 10, 'sort_order' => 1]);
    }

    private function makeServer(array $overrides = []): FtpServer
    {
        return FtpServer::create(array_merge([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21, 'username' => 'u', 'password' => 'p',
            'path' => '/results', 'server_type' => 'scheduled', 'league_id' => $this->league->id,
            'game' => 'acc', 'platform' => 'console', 'active' => true,
        ], $overrides));
    }

    // Round 1 in summer time (BST), round 2 a week later after the clocks went back (GMT).
    private function makeRounds(?FtpServer $server = null): void
    {
        foreach ([1 => '2026-10-18 20:00', 2 => '2026-10-25 20:00'] as $number => $london) {
            $race = Race::create([
                'championship_id' => $this->championship->id, 'round_number' => $number,
                'title' => 'Sunday League — Round '.$number, 'track' => $number === 1 ? 'monza' : 'spa', 'game' => 'acc',
                'status' => 'finished', 'is_championship' => true, 'event_tag' => 'championship',
                'scheduled_at' => Carbon::parse($london, 'Europe/London')->utc(),
                'race_duration' => 40, 'weather' => 'wet', 'rain_level' => 0.4, 'ftp_server_id' => $server?->id,
            ]);
            RaceResult::create([
                'race_id' => $race->id, 'session_type' => 'race', 'driver_name' => 'X', 'player_id' => 'M1',
                'position' => 1, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
            ]);
        }
        $this->championship->registrations()->create(['user_id' => User::factory()->create()->id]);
    }

    private function duplicate(array $input)
    {
        return $this->actingAs($this->manager)
            ->post(route('admin.leagues.championships.duplicate.store', [$this->league, $this->championship]), $input);
    }

    private function copy(): Championship
    {
        return Championship::withoutTenantScope()->whereKeyNot($this->championship->id)->latest('id')->firstOrFail();
    }

    public function test_it_copies_settings_classes_and_rounds_into_a_new_draft_season(): void
    {
        $server = $this->makeServer();
        $this->makeRounds($server);

        $this->duplicate(['name' => 'Sunday League S2', 'season' => 2027, 'copy_rounds' => 1, 'first_round_at' => '2027-03-14T20:00'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $copy = $this->copy();
        $this->assertSame('Sunday League S2', $copy->name);
        $this->assertSame(2027, (int) $copy->season);
        $this->assertSame('draft', $copy->status);
        $this->assertFalse((bool) $copy->xcl_rating_enabled);
        $this->assertTrue($copy->is_multiclass);
        $this->assertSame(['GT3', 'GT4'], $copy->classes()->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(0, $copy->registrations()->count());

        $balance = $copy->settings->balance;
        $this->assertSame('5, 3, 0, -2', $balance->success_ballast_kg);
        $this->assertSame([], (array) $balance->adjustments);
        $this->assertNull($copy->settings->requirements->registration_opens_at);

        $rounds = $copy->rounds()->get();
        $this->assertSame([1, 2], $rounds->pluck('round_number')->all());
        $this->assertSame(['monza', 'spa'], $rounds->pluck('track')->all());
        // Same UK wall-clock time, a week apart, even though the originals straddled a clock change.
        $this->assertSame(['2027-03-14 20:00', '2027-03-21 20:00'],
            $rounds->map(fn (Race $r) => $r->scheduled_at->copy()->tz('Europe/London')->format('Y-m-d H:i'))->all());
        $this->assertSame('Sunday League S2 — Round 1', $rounds[0]->title);
        $this->assertSame('open', $rounds[0]->status);
        $this->assertSame('wet', $rounds[0]->weather);
        $this->assertEquals(0.4, $rounds[0]->rain_level);
        $this->assertSame($server->id, $rounds[0]->ftp_server_id);
        $this->assertSame('pending', $rounds[0]->config_push_status);
        $this->assertSame(0, RaceResult::whereIn('race_id', $rounds->pluck('id'))->count());

        // The original is untouched.
        $this->assertSame(2, $this->championship->rounds()->count());
        $this->assertSame(1, $this->championship->registrations()->count());
    }

    public function test_a_round_whose_slot_is_taken_is_copied_without_its_server(): void
    {
        $server = $this->makeServer();
        $this->makeRounds($server);
        // Someone else already holds round 2's new slot on that server.
        Race::create([
            'title' => 'Other event', 'track' => 'imola', 'game' => 'acc', 'status' => 'open', 'ftp_server_id' => $server->id,
            'scheduled_at' => Carbon::parse('2027-03-21 20:00', 'Europe/London')->utc(),
            'slot_time' => Carbon::parse('2027-03-21 20:00', 'Europe/London')->utc(),
        ]);

        $this->duplicate(['name' => 'S2', 'season' => 2027, 'copy_rounds' => 1, 'first_round_at' => '2027-03-14T20:00'])
            ->assertSessionHas('success', fn ($message) => str_contains($message, '1 round lost their server'));

        $rounds = $this->copy()->rounds()->get();
        $this->assertCount(2, $rounds);
        $this->assertSame($server->id, $rounds[0]->ftp_server_id);
        $this->assertNull($rounds[1]->ftp_server_id);
    }

    public function test_rounds_are_optional_and_a_start_date_is_needed_when_copied(): void
    {
        $this->makeRounds();

        $this->duplicate(['name' => 'S2', 'season' => 2027, 'copy_rounds' => 1])
            ->assertSessionHasErrors('first_round_at');
        $this->duplicate(['name' => 'S2', 'season' => 2027, 'copy_rounds' => 1, 'first_round_at' => '2026-01-01T20:00'])
            ->assertSessionHasErrors('first_round_at');

        $this->duplicate(['name' => 'S2', 'season' => 2027, 'copy_rounds' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $this->copy()->rounds()->count());
        $this->assertSame(2, $this->copy()->classes()->count());
    }

    public function test_the_list_links_to_it_and_another_leagues_manager_cannot_use_it(): void
    {
        $this->actingAs($this->manager)->get(route('admin.leagues.championships.index', $this->league))
            ->assertOk()->assertSee(route('admin.leagues.championships.duplicate', [$this->league, $this->championship]));
        $this->actingAs($this->manager)->get(route('admin.leagues.championships.duplicate', [$this->league, $this->championship]))
            ->assertOk()->assertSee('New season of Sunday League');

        $other = League::create(['name' => 'Other', 'slug' => 'other', 'primary_color' => '#000000', 'accent_color' => '#000000', 'status' => 'active']);
        $outsider = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $other->id, 'user_id' => $outsider->id, 'role' => 'manager']);
        $outsider->syncLeagueRoleFlags();

        $this->actingAs($outsider->refresh())
            ->post(route('admin.leagues.championships.duplicate.store', [$this->league, $this->championship]), ['name' => 'Stolen', 'season' => 2027])
            ->assertNotFound();
        $this->assertSame(1, Championship::withoutTenantScope()->count());
    }
}
