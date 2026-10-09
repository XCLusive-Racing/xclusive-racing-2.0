<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback (MTSS, 2026-10): a team car has a fixed line-up of 2, plus a reserve
// picked afterwards (Edit next to Withdraw) who can swap in for one driver per round.
class ChampionshipReserveDriverTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private array $drivers;

    private RacingTeam $team;

    private Championship $championship;

    private Race $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->drivers = User::factory()->count(4)->create()->all();
        $this->team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $this->owner->id]);
        $this->team->members()->attach(collect($this->drivers)->pluck('id'));

        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $league->id, 'name' => 'MTSS', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $this->championship->settings = array_replace_recursive($this->championship->settings->toArray(), [
            'format' => ['driver_swaps_enabled' => true, 'team_registration_scope' => 'championship', 'max_drivers_per_car' => 2, 'max_cars_per_team' => 2],
            'scoring' => ['team_points_enabled' => true],
        ]);
        $this->championship->save();

        $this->round = Race::create([
            'championship_id' => $this->championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);

        // Car #11: drivers 0 and 1, driver 0 starts.
        $this->actingAs($this->owner)->post(route('championships.register', $this->championship), [
            'racing_team_id' => $this->team->id, 'driver_ids' => [$this->drivers[0]->id, $this->drivers[1]->id],
            'starting_driver_id' => $this->drivers[0]->id, 'car_number' => 11,
        ])->assertSessionHas('success');
    }

    private function car(): ChampionshipRegistration
    {
        return $this->championship->registrations()->where('car_number', 11)->sole();
    }

    private function entry(): RaceTeamEntry
    {
        return RaceTeamEntry::where('race_id', $this->round->id)->where('car_number', 11)->sole();
    }

    private function driversInCar(): array
    {
        return RaceRegistration::where('team_entry_id', $this->entry()->id)->pluck('user_id')->sort()->values()->all();
    }

    private function setReserve(?User $reserve)
    {
        return $this->actingAs($this->owner)->put(route('championships.cars.reserve', [$this->championship, $this->car()]), [
            'reserve_driver_id' => $reserve?->id,
        ]);
    }

    public function test_a_reserve_is_picked_after_registration_and_is_not_entered_into_rounds(): void
    {
        $this->actingAs($this->owner)->get(route('championships.show', $this->championship))
            ->assertOk()->assertSee('Withdraw')->assertSee('Edit');

        $this->setReserve($this->drivers[2])->assertSessionHas('success');

        $this->assertSame($this->drivers[2]->id, $this->car()->reserve_driver_id);
        $this->assertSame(collect([$this->drivers[0]->id, $this->drivers[1]->id])->sort()->values()->all(), $this->driversInCar());
        // A reserve is taken: no other car can pick them as a driver.
        $this->assertTrue($this->championship->driverIdsInCars()->contains($this->drivers[2]->id));
    }

    public function test_the_reserve_must_be_a_free_team_member(): void
    {
        $this->setReserve($this->drivers[0])->assertSessionHas('error', 'That driver is already in a car of this championship.');
        $this->setReserve(User::factory()->create())->assertSessionHas('error', 'The reserve must be a member of your team.');
        $this->assertNull($this->car()->reserve_driver_id);

        $this->actingAs($this->drivers[3])->put(route('championships.cars.reserve', [$this->championship, $this->car()]), [
            'reserve_driver_id' => $this->drivers[2]->id,
        ])->assertForbidden();
    }

    public function test_the_reserve_swaps_in_for_one_round_and_back(): void
    {
        $this->setReserve($this->drivers[2]);
        [$starter, $second, $reserve] = $this->drivers;

        $this->actingAs($this->owner)->get(route('events.show', $this->round))
            ->assertOk()->assertSee('Swap with '.$reserve->displayName());

        // The starting driver sits out: the reserve takes their seat and the start.
        $this->actingAs($this->owner)->post(route('events.swap-team-driver', [$this->round, $this->entry()]), [
            'driver_id' => $starter->id, 'with_id' => $reserve->id,
        ])->assertSessionHas('success');

        $this->assertSame(collect([$second->id, $reserve->id])->sort()->values()->all(), $this->driversInCar());
        $this->assertSame($reserve->id, $this->entry()->starting_driver_id);
        // The championship line-up itself is unchanged.
        $this->assertSame([$starter->id, $second->id], $this->car()->driverIds());

        // And back: the driver who sat out is now the one to swap with.
        $this->actingAs($this->owner)->post(route('events.swap-team-driver', [$this->round, $this->entry()]), [
            'driver_id' => $reserve->id, 'with_id' => $starter->id,
        ])->assertSessionHas('success');
        $this->assertSame(collect([$starter->id, $second->id])->sort()->values()->all(), $this->driversInCar());
    }

    public function test_no_swap_without_a_reserve_or_after_the_round_closed(): void
    {
        $this->actingAs($this->owner)->post(route('events.swap-team-driver', [$this->round, $this->entry()]), [
            'driver_id' => $this->drivers[0]->id, 'with_id' => $this->drivers[2]->id,
        ])->assertSessionHas('error', 'That swap is not possible for this car.');

        $this->setReserve($this->drivers[2]);
        $this->round->update(['status' => 'closed']);

        $this->actingAs($this->owner)->post(route('events.swap-team-driver', [$this->round, $this->entry()]), [
            'driver_id' => $this->drivers[0]->id, 'with_id' => $this->drivers[2]->id,
        ])->assertSessionHas('error');
        $this->assertNotContains($this->drivers[2]->id, $this->driversInCar());
    }

    public function test_a_round_the_reserve_drove_counts_for_the_car(): void
    {
        $this->setReserve($this->drivers[2]);

        $this->assertContains($this->drivers[2]->id, $this->car()->scoringDriverIds());
        $this->assertNotContains($this->drivers[2]->id, $this->car()->driverIds());
    }
}
