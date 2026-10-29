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

// User-directed 2026-10-29 (like SimGrid): a team edits a car's line-up with
// Manage Entry instead of withdrawing and registering again.
class ChampionshipLineupTest extends TestCase
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

    private function driversInRound(): array
    {
        $entry = RaceTeamEntry::where('race_id', $this->round->id)->where('car_number', 11)->sole();

        return RaceRegistration::where('team_entry_id', $entry->id)->pluck('user_id')->sort()->values()->all();
    }

    private function lineup(array $drivers, User $starter, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->owner)->put(route('championships.cars.lineup', [$this->championship, $this->car()]), [
            'driver_ids' => collect($drivers)->pluck('id')->all(), 'starting_driver_id' => $starter->id,
        ]);
    }

    public function test_changing_the_lineup_updates_the_car_and_its_upcoming_rounds(): void
    {
        $this->lineup([$this->drivers[0], $this->drivers[2]], $this->drivers[2])->assertSessionHas('success');

        $car = $this->car();
        $this->assertSame([$this->drivers[0]->id, $this->drivers[2]->id], $car->driverIds());
        $this->assertSame($this->drivers[2]->id, $car->starting_driver_id);
        $this->assertSame(collect([$this->drivers[0]->id, $this->drivers[2]->id])->sort()->values()->all(), $this->driversInRound());
        $this->assertSame($this->drivers[2]->id, RaceTeamEntry::where('race_id', $this->round->id)->sole()->starting_driver_id);
        $this->assertSame(1, $this->championship->registrations()->count(), 'No withdraw and re-register.');
    }

    public function test_a_reserve_moved_into_the_lineup_stops_being_the_reserve(): void
    {
        $this->car()->update(['reserve_driver_id' => $this->drivers[2]->id]);

        $this->lineup([$this->drivers[0], $this->drivers[2]], $this->drivers[0])->assertSessionHas('success');

        $this->assertNull($this->car()->reserve_driver_id);
    }

    public function test_lineup_rules_match_registration(): void
    {
        $this->lineup([$this->drivers[0], $this->drivers[1], $this->drivers[2]], $this->drivers[0])
            ->assertSessionHas('error', 'Pick at most 2 driver(s) for your car.');
        $this->lineup([$this->drivers[0], User::factory()->create()], $this->drivers[0])
            ->assertSessionHas('error', 'Every driver must be a member of your team.');
        $this->lineup([$this->drivers[0]], $this->drivers[1])
            ->assertSessionHas('error', 'The starting driver must be one of the selected drivers.');

        $this->assertSame([$this->drivers[0]->id, $this->drivers[1]->id], $this->car()->driverIds());
    }

    public function test_a_driver_in_another_car_cannot_be_picked(): void
    {
        $this->actingAs($this->owner)->post(route('championships.register', $this->championship), [
            'racing_team_id' => $this->team->id, 'driver_ids' => [$this->drivers[2]->id],
            'starting_driver_id' => $this->drivers[2]->id, 'car_number' => 22,
        ])->assertSessionHas('success');

        $this->lineup([$this->drivers[0], $this->drivers[2]], $this->drivers[0])
            ->assertSessionHas('error', 'A driver you picked is already in another car of this championship.');
    }

    public function test_only_the_team_manager_can_change_the_lineup(): void
    {
        $this->lineup([$this->drivers[0], $this->drivers[2]], $this->drivers[0], $this->drivers[3])->assertForbidden();
    }
}
