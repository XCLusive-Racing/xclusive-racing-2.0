<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceClass;
use App\Models\RaceRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09-18: resaving the real "Multiclass / Spa" race in the admin
// panel wiped every existing registration's race_class_id, making 50 already-signed-up
// drivers disappear from the event page entirely (they didn't match either class's id
// any more, and weren't over capacity either, so they showed nowhere).
// Root cause: RaceController::syncRaceClasses() unconditionally deleted every
// RaceClass row and recreated fresh ones (new ids) on *every* save, regardless of
// whether the submitted classes actually changed -- race_class_id is nullOnDelete, so
// recreating a class with identical values still silently disconnected every
// registration pointing at the old row. Fixed by matching submitted classes against
// existing ones (by car_class, the stable key from the admin's fixed GT3/GT4/GT2/TCX/
// GTC picker) and updating in place; only a class genuinely removed from the submitted
// set is deleted now.
class RaceClassSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function makeMulticlassRace(): Race
    {
        return Race::create([
            'title' => 'Multiclass Spa', 'track' => 'Spa', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
            'is_multiclass' => true, 'max_drivers' => 10, 'race_duration' => 30,
        ]);
    }

    private function updatePayload(Race $race, array $classes, array $overrides = []): array
    {
        return array_merge([
            'game' => 'acc', 'track' => $race->track, 'status' => 'open',
            'scheduled_at' => $race->scheduled_at->timezone('Europe/London')->format('Y-m-d\TH:i'),
            'title' => $race->title, 'race_duration' => 30,
            'is_multiclass' => '1',
            'classes_json' => json_encode($classes),
        ], $overrides);
    }

    public function test_resaving_a_race_with_the_same_classes_preserves_registrations_class_link(): void
    {
        $admin = $this->makeAdmin();
        $race = $this->makeMulticlassRace();
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'car_class' => 'GT3', 'sort_order' => 0]);
        $gt4 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT4', 'car_class' => 'GT4', 'sort_order' => 1]);

        $driver = User::factory()->create();
        $registration = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driver->id, 'race_class_id' => $gt3->id]);

        $payload = $this->updatePayload($race, [
            ['name' => 'GT3', 'car_class' => 'GT3', 'color' => '#7c3aed', 'max_drivers' => null],
            ['name' => 'GT4', 'car_class' => 'GT4', 'color' => '#2563eb', 'max_drivers' => null],
        ]);

        $this->actingAs($admin)->put(route('admin.races.update', $race), $payload)->assertRedirect();

        $registration->refresh();
        $this->assertSame($gt3->id, $registration->race_class_id, 'Resaving the same classes must not disconnect existing registrations.');
        $this->assertSame(2, RaceClass::where('race_id', $race->id)->count(), 'Existing class rows should be updated in place, not duplicated.');
    }

    public function test_removing_a_class_on_resave_only_orphans_that_classs_registrations(): void
    {
        $admin = $this->makeAdmin();
        $race = $this->makeMulticlassRace();
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'car_class' => 'GT3', 'sort_order' => 0]);
        $gt4 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT4', 'car_class' => 'GT4', 'sort_order' => 1]);

        $gt3Driver = User::factory()->create();
        $gt4Driver = User::factory()->create();
        $gt3Reg = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $gt3Driver->id, 'race_class_id' => $gt3->id]);
        $gt4Reg = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $gt4Driver->id, 'race_class_id' => $gt4->id]);

        // GT4 dropped from the submitted set -- only its own registrations should lose
        // their class link; GT3's must be untouched.
        $payload = $this->updatePayload($race, [
            ['name' => 'GT3', 'car_class' => 'GT3', 'color' => '#7c3aed', 'max_drivers' => null],
        ]);

        $this->actingAs($admin)->put(route('admin.races.update', $race), $payload)->assertRedirect();

        $gt3Reg->refresh();
        $gt4Reg->refresh();
        $this->assertSame($gt3->id, $gt3Reg->race_class_id);
        $this->assertNull($gt4Reg->race_class_id);
        $this->assertSame(1, RaceClass::where('race_id', $race->id)->count());
    }

    public function test_adding_a_new_class_on_resave_keeps_the_existing_ones_intact(): void
    {
        $admin = $this->makeAdmin();
        $race = $this->makeMulticlassRace();
        $gt3 = RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'car_class' => 'GT3', 'sort_order' => 0]);

        $driver = User::factory()->create();
        $registration = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driver->id, 'race_class_id' => $gt3->id]);

        $payload = $this->updatePayload($race, [
            ['name' => 'GT3', 'car_class' => 'GT3', 'color' => '#7c3aed', 'max_drivers' => null],
            ['name' => 'GT4', 'car_class' => 'GT4', 'color' => '#2563eb', 'max_drivers' => null],
        ]);

        $this->actingAs($admin)->put(route('admin.races.update', $race), $payload)->assertRedirect();

        $registration->refresh();
        $this->assertSame($gt3->id, $registration->race_class_id);
        $this->assertSame(2, RaceClass::where('race_id', $race->id)->count());
    }
}
