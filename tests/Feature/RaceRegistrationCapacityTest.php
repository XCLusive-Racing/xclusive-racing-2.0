<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09: "we hebben ook een keer de max aantal mensen gehaald
// voor een baan en de registration stopt niet bij het maximum maar gaat door"
// -- RaceController::register()/registerTeam() checked isFull()/max_drivers
// *before* opening the DB transaction that actually inserts the registration,
// with no row lock -- a classic check-then-act race: two people registering
// for the last spot at the same moment could both pass the check before
// either insert commits, letting the race fill past max_drivers. Fixed by
// re-checking against a locked Race/RaceClass row inside the transaction.
// True concurrency isn't exercisable here (single-threaded PHPUnit, SQLite),
// so this locks in the corrected control flow itself: the authoritative
// check now lives inside the transaction and correctly rejects a request
// once the cap is reached, not just on the (no longer load-bearing) early one.
class RaceRegistrationCapacityTest extends TestCase
{
    use RefreshDatabase;

    private function makeRace(array $overrides = []): Race
    {
        return Race::create(array_merge([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ], $overrides));
    }

    public function test_solo_registration_still_succeeds_when_the_race_has_room(): void
    {
        $race = $this->makeRace(['max_drivers' => 2]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.register', $race))
            ->assertRedirect();

        $this->assertDatabaseHas('race_registrations', ['race_id' => $race->id, 'user_id' => $user->id]);
    }

    public function test_solo_registration_is_rejected_once_the_race_is_at_capacity(): void
    {
        $race = $this->makeRace(['max_drivers' => 1]);
        $existing = User::factory()->create();
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $existing->id]);

        $newUser = User::factory()->create();

        $this->actingAs($newUser)
            ->post(route('events.register', $race))
            ->assertRedirect();

        $this->assertDatabaseMissing('race_registrations', ['race_id' => $race->id, 'user_id' => $newUser->id]);
        $this->assertSame(1, RaceRegistration::where('race_id', $race->id)->count());
    }

    public function test_team_registration_is_rejected_once_the_race_is_at_team_capacity(): void
    {
        $race = $this->makeRace(['max_drivers' => 1, 'is_endurance' => true]);

        $existingOwner = User::factory()->create();
        $existingTeam  = RacingTeam::create(['name' => 'Apex', 'tag' => 'APX', 'owner_id' => $existingOwner->id]);
        RaceTeamEntry::create([
            'race_id' => $race->id, 'racing_team_id' => $existingTeam->id,
            'car_number' => 1, 'starting_driver_id' => $existingOwner->id,
        ]);

        $newOwner = User::factory()->create();
        RacingTeam::create(['name' => 'Vector', 'tag' => 'VEC', 'owner_id' => $newOwner->id]);

        $this->actingAs($newOwner)->post(route('events.register-team', $race), [
            'car_number' => 2, 'driver_ids' => [$newOwner->id], 'starting_driver_id' => $newOwner->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('race_team_entries', ['race_id' => $race->id, 'racing_team_id' => RacingTeam::where('name', 'Vector')->value('id')]);
        $this->assertSame(1, RaceTeamEntry::where('race_id', $race->id)->count());
    }

    public function test_team_registration_is_rejected_when_the_car_number_is_already_taken(): void
    {
        $race = $this->makeRace(['is_endurance' => true]);

        $existingOwner = User::factory()->create();
        $existingTeam  = RacingTeam::create(['name' => 'Apex', 'tag' => 'APX', 'owner_id' => $existingOwner->id]);
        RaceTeamEntry::create([
            'race_id' => $race->id, 'racing_team_id' => $existingTeam->id,
            'car_number' => 7, 'starting_driver_id' => $existingOwner->id,
        ]);

        $newOwner = User::factory()->create();
        RacingTeam::create(['name' => 'Vector', 'tag' => 'VEC', 'owner_id' => $newOwner->id]);

        $this->actingAs($newOwner)->post(route('events.register-team', $race), [
            'car_number' => 7, 'driver_ids' => [$newOwner->id], 'starting_driver_id' => $newOwner->id,
        ])->assertRedirect();

        $this->assertSame(1, RaceTeamEntry::where('race_id', $race->id)->where('car_number', 7)->count());
    }
}
