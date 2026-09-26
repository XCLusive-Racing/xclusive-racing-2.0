<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Message;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\RacingTeam;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Wizard options that used to be display-only: Manual Approval of Entries, Use XCL
// Stewarding and Post-Race Time Penalties (Formation Lap: ChampionshipFixedWeatherDefaultsTest).
class ChampionshipRuleEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(array $settings = []): Championship
    {
        $championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'points_system' => [25, 18], 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), $settings);
        $championship->save();

        return $championship;
    }

    private function makeManager(): User
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        return $manager->refresh();
    }

    private function finishedRound(Championship $championship): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDay(),
        ]);
    }

    // --- Manual approval ---

    public function test_a_manual_approval_entry_waits_and_does_not_count_until_approved(): void
    {
        $championship = $this->makeChampionship(['requirements' => ['manual_approval_required' => true]]);
        $driver = User::factory()->create(['name' => 'Pending Pete']);

        $this->actingAs($driver)->post(route('championships.register', $championship))
            ->assertSessionHas('success', 'Your entry has been received — the league will review it before it is confirmed.');

        $registration = $championship->registrations()->firstOrFail();
        $this->assertTrue($registration->isPending());
        $this->assertSame([], $championship->computeStandings());

        $this->actingAs($driver)->get(route('championships.show', $championship))
            ->assertSee('Your entry is waiting for approval by the league.');

        $manager = $this->makeManager();
        $this->actingAs($manager)->get(route('admin.leagues.championships.entries.index', [$this->league, $championship]))
            ->assertOk()->assertSee('Pending Pete')->assertSee('Pending');

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.entries.approve', [$this->league, $championship, $registration]))
            ->assertSessionHas('success');

        $this->assertFalse($registration->fresh()->isPending());
        // refresh(): standings are cached per instance, like within one request.
        $this->assertCount(1, $championship->refresh()->computeStandings());
        $this->assertTrue(Message::where('user_id', $driver->id)->where('type', 'championship_entry')->exists());
    }

    public function test_rejecting_a_pending_entry_removes_it_and_tells_the_driver(): void
    {
        $championship = $this->makeChampionship(['requirements' => ['manual_approval_required' => true]]);
        $driver = User::factory()->create();
        $registration = ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $driver->id, 'approved_at' => null,
        ]);

        $this->actingAs($this->makeManager())
            ->delete(route('admin.leagues.championships.entries.reject', [$this->league, $championship, $registration]))
            ->assertSessionHas('success');

        $this->assertModelMissing($registration);
        $this->assertTrue(Message::where('user_id', $driver->id)->where('type', 'championship_entry')->exists());
    }

    public function test_entries_are_approved_straight_away_without_manual_approval(): void
    {
        $championship = $this->makeChampionship();
        $driver = User::factory()->create();

        $this->actingAs($driver)->post(route('championships.register', $championship))->assertSessionHas('success');

        $this->assertFalse($championship->registrations()->firstOrFail()->isPending());
    }

    public function test_a_pending_whole_championship_team_car_is_only_entered_into_rounds_once_approved(): void
    {
        $championship = $this->makeChampionship([
            'requirements' => ['manual_approval_required' => true],
            'format' => ['driver_swaps_enabled' => true, 'team_registration_scope' => 'championship', 'max_drivers_per_car' => 2],
        ]);
        $round = Race::create([
            'championship_id' => $championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);

        $this->actingAs($owner)->post(route('championships.register', $championship), [
            'racing_team_id' => $team->id, 'driver_ids' => [$owner->id], 'starting_driver_id' => $owner->id, 'car_number' => 7,
        ])->assertSessionHas('success');
        $this->assertSame(0, $round->teamEntries()->count());

        $registration = $championship->registrations()->firstOrFail();
        $this->actingAs($this->makeManager())
            ->post(route('admin.leagues.championships.entries.approve', [$this->league, $championship, $registration]));

        $this->assertSame(1, $round->teamEntries()->count());
    }

    public function test_only_the_leagues_own_staff_can_approve(): void
    {
        $championship = $this->makeChampionship(['requirements' => ['manual_approval_required' => true]]);
        $registration = ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => User::factory()->create()->id, 'approved_at' => null,
        ]);

        // TenantScope hides another league's championship entirely, so a 404.
        $this->actingAs(User::factory()->create())
            ->post(route('admin.leagues.championships.entries.approve', [$this->league, $championship, $registration]))
            ->assertNotFound();

        $this->assertTrue($registration->fresh()->isPending());
    }

    // --- XCL stewarding ---

    private function reportPayload(Race $race, User $reported): array
    {
        return [
            'race_id' => $race->id, 'reported_user_id' => $reported->id, 'session_type' => 'R',
            'description' => 'Divebombed me into turn one and never gave the place back.',
            'video_url' => 'https://youtu.be/example',
        ];
    }

    public function test_a_championship_without_xcl_stewarding_takes_no_reports(): void
    {
        $race = $this->finishedRound($this->makeChampionship(['penalties' => ['stewarding_enabled' => false]]));
        [$reporter, $reported] = User::factory()->count(2)->create()->all();
        foreach ([$reporter, $reported] as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        $this->actingAs($reporter)->get(route('reports.index'))
            ->assertOk()->assertViewHas('races', fn ($races) => $races->isEmpty());

        $this->actingAs($reporter)->post(route('reports.store'), $this->reportPayload($race, $reported))
            ->assertSessionHasErrors('race_id');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_a_championship_with_xcl_stewarding_takes_reports(): void
    {
        $race = $this->finishedRound($this->makeChampionship(['penalties' => ['stewarding_enabled' => true]]));
        [$reporter, $reported] = User::factory()->count(2)->create()->all();
        foreach ([$reporter, $reported] as $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
        }

        $this->actingAs($reporter)->post(route('reports.store'), $this->reportPayload($race, $reported))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('reports', 1);
    }

    // --- Post-race time penalties ---

    private function timePenaltyAttempt(Championship $championship): RaceResult
    {
        $race = $this->finishedRound($championship);
        $result = RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'driver_name' => 'Driver',
            'position' => 1, 'lap_count' => 20, 'total_time' => 1_200_000,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($admin)
            ->post(route('admin.races.results.time-penalty', [$race, $result]), ['penalty_seconds' => 5])
            ->assertRedirect();

        return $result->fresh();
    }

    public function test_time_penalties_are_blocked_when_the_championship_switched_them_off(): void
    {
        $result = $this->timePenaltyAttempt($this->makeChampionship(['penalties' => ['post_race_time_penalties_enabled' => false]]));

        $this->assertSame(0, (int) $result->time_penalty_ms);
    }

    public function test_time_penalties_apply_when_the_championship_allows_them(): void
    {
        $result = $this->timePenaltyAttempt($this->makeChampionship(['penalties' => ['post_race_time_penalties_enabled' => true]]));

        $this->assertSame(5_000, (int) $result->time_penalty_ms);
    }
}
