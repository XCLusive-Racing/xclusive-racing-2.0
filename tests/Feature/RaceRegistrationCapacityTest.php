<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceClass;
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
// so this locks in the corrected control flow itself: the lock still
// serializes concurrent registrations for a stable FIFO order.
//
// Follow-up (2026-09-18): solo/multiclass registration no longer hard-rejects
// once a race/class is full -- it joins a FIFO waiting list instead (see
// Race::isRegistrationWaitlisted()) and is promoted automatically the moment
// a spot opens up (RaceController::promoteNextWaitlisted()). Team/endurance
// registration (registerTeam()) is unchanged and still hard-rejects once full.
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

    public function test_solo_registration_joins_the_waiting_list_once_the_race_is_at_capacity(): void
    {
        $race = $this->makeRace(['max_drivers' => 1]);
        $existing = User::factory()->create();
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $existing->id]);

        $newUser = User::factory()->create();

        $this->actingAs($newUser)
            ->post(route('events.register', $race))
            ->assertRedirect();

        $registration = RaceRegistration::where('race_id', $race->id)->where('user_id', $newUser->id)->first();
        $this->assertNotNull($registration, 'A full race should still create the registration, just waitlisted.');
        $this->assertTrue($race->isRegistrationWaitlisted($registration));
        $this->assertSame(1, $race->waitlistPosition($registration));
        $this->assertSame(2, RaceRegistration::where('race_id', $race->id)->count());
    }

    public function test_waitlisted_driver_is_promoted_when_a_slot_opens_up(): void
    {
        $race = $this->makeRace(['max_drivers' => 1, 'status' => 'open']);
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post(route('events.register', $race));
        $this->actingAs($second)->post(route('events.register', $race));

        $secondReg = RaceRegistration::where('race_id', $race->id)->where('user_id', $second->id)->first();
        $this->assertTrue($race->isRegistrationWaitlisted($secondReg));

        $this->actingAs($first)
            ->delete(route('events.unregister', $race))
            ->assertRedirect();

        $this->assertSoftDeleted('race_registrations', ['race_id' => $race->id, 'user_id' => $first->id]);

        $secondReg->refresh();
        $this->assertFalse($race->isRegistrationWaitlisted($secondReg));

        $this->assertDatabaseHas('messages', [
            'user_id' => $second->id,
            'title' => "You're in: ".$race->title,
        ]);
    }

    public function test_waitlist_is_per_class_in_a_multiclass_race(): void
    {
        $race = $this->makeRace(['is_multiclass' => true]);
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'max_drivers' => 1, 'sort_order' => 1]);
        $gt4 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT4', 'max_drivers' => 1, 'sort_order' => 2]);

        $gt3First = User::factory()->create();
        $gt3Second = User::factory()->create();
        $gt4First = User::factory()->create();

        $this->actingAs($gt3First)->post(route('events.register', $race), ['race_class_id' => $gt3->id]);
        $this->actingAs($gt3Second)->post(route('events.register', $race), ['race_class_id' => $gt3->id]);
        $this->actingAs($gt4First)->post(route('events.register', $race), ['race_class_id' => $gt4->id]);

        $gt3SecondReg = RaceRegistration::where('user_id', $gt3Second->id)->first();
        $gt4FirstReg = RaceRegistration::where('user_id', $gt4First->id)->first();

        // GT3's second entrant is waitlisted (GT3 is full), but GT4's first entrant is
        // not -- each class has its own independent cap/queue.
        $this->assertTrue($race->isRegistrationWaitlisted($gt3SecondReg));
        $this->assertFalse($race->isRegistrationWaitlisted($gt4FirstReg));
    }

    // Regression test for the real "Multiclass / Spa" race in production: a multiclass
    // race with a race-wide max_drivers set, but its classes have no cap of their own
    // (max_drivers null, unlimited per class). Picking an uncapped class must NOT bypass
    // the race-wide cap -- previously it did, because isRegistrationWaitlisted()
    // delegated entirely to the class and an uncapped class is never "full" on its own.
    public function test_uncapped_class_still_respects_the_race_wide_cap_in_a_multiclass_race(): void
    {
        $race = $this->makeRace(['is_multiclass' => true, 'max_drivers' => 2]);
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'max_drivers' => null, 'sort_order' => 1]);

        $first = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();

        $this->actingAs($first)->post(route('events.register', $race), ['race_class_id' => $gt3->id]);
        $this->actingAs($second)->post(route('events.register', $race), ['race_class_id' => $gt3->id]);
        $this->actingAs($third)->post(route('events.register', $race), ['race_class_id' => $gt3->id]);

        $thirdReg = RaceRegistration::where('user_id', $third->id)->first();

        $this->assertFalse($gt3->isFull(), 'The class itself has no cap, so it never reports full on its own.');
        $this->assertTrue($race->isRegistrationWaitlisted($thirdReg), 'The race-wide cap must still apply even though the class is uncapped.');
        $this->assertSame(1, $race->waitlistPosition($thirdReg));

        // Unregistering the first entrant frees the race-wide slot and promotes the
        // third entrant (FIFO), even though it all happened within one uncapped class.
        $this->actingAs($first)->delete(route('events.unregister', $race));

        $thirdReg->refresh();
        $this->assertFalse($race->isRegistrationWaitlisted($thirdReg));
        $this->assertDatabaseHas('messages', [
            'user_id' => $third->id,
            'title' => "You're in: ".$race->title,
        ]);
    }

    public function test_team_registration_is_rejected_once_the_race_is_at_team_capacity(): void
    {
        $race = $this->makeRace(['max_drivers' => 1, 'is_endurance' => true]);

        $existingOwner = User::factory()->create();
        $existingTeam = RacingTeam::create(['name' => 'Apex', 'tag' => 'APX', 'owner_id' => $existingOwner->id]);
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
        $existingTeam = RacingTeam::create(['name' => 'Apex', 'tag' => 'APX', 'owner_id' => $existingOwner->id]);
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
