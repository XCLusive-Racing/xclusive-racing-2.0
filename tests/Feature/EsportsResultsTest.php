<?php

namespace Tests\Feature;

use App\Models\EsportsDriver;
use App\Models\Result;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Coverage for the new esports_manager role, the moved Team Event admin
// section, and the DB-backed Results system (replacing the hardcoded Pro
// results array).
class EsportsResultsTest extends TestCase
{
    use RefreshDatabase;

    private function esportsManager(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'esports_manager')->firstOrFail());

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->firstOrFail());

        return $user;
    }

    public function test_pro_driver_results_are_migrated_from_the_old_hardcoded_array(): void
    {
        $results = Result::legacyProArrayForSubject('dirk-schouten');

        $this->assertArrayHasKey(2024, $results);
        $benelux = collect($results[2024])->firstWhere('championship', 'Porsche Carrera Cup Benelux');
        $this->assertNotNull($benelux);
        $this->assertSame('Champion', $benelux['standing']);
        $this->assertSame(['P10', 'P1'], $benelux['races'][0]['positions']);
        $this->assertSame('Spa-Francorchamps', $benelux['races'][0]['track']);

        $supercup = collect($results[2025])->firstWhere('championship', 'Porsche Mobil 1 Supercup');
        $this->assertSame('Rookie', $supercup['races'][0]['class']);
    }

    public function test_esports_manager_can_access_the_esports_admin_section(): void
    {
        $manager = $this->esportsManager();

        $this->actingAs($manager)->get(route('admin.results.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.team-events.index'))->assertOk();
    }

    public function test_plain_admin_cannot_access_the_esports_admin_section(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.results.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.team-events.index'))->assertForbidden();
    }

    public function test_esports_manager_sees_the_esports_nav_section_and_admin_does_not(): void
    {
        $manager = $this->esportsManager();
        $admin = $this->admin();

        $this->actingAs($manager)->get(route('admin.results.index'))->assertSee('Esports')->assertSee('Results');
        $this->actingAs($admin)->get(route('admin.races.index'))->assertDontSee('>Esports<', false);
    }

    public function test_esports_manager_can_create_a_team_result_visible_on_both_drivers_pages(): void
    {
        $manager = $this->esportsManager();

        $driverOne = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'acc', 'sort_order' => 1]);
        $driverTwo = EsportsDriver::create(['name' => 'Sam Speed', 'slug' => 'sam-speed', 'game' => 'acc', 'sort_order' => 2]);

        $response = $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'acc-team',
            'event_date' => '2026-05-10',
            'title' => 'XCL Endurance Round 1',
            'track' => 'Spa-Francorchamps',
            'car_class' => 'GT3',
            'driver_positions' => [
                $driverOne->id => 'P2',
                $driverTwo->id => 'DNF',
            ],
        ]);

        $response->assertRedirect(route('admin.results.index'));

        $result = Result::where('subject', 'acc-team')->firstOrFail();
        $this->assertSame('esports', $result->category);
        $this->assertSame(2026, $result->year);
        $this->assertCount(1, $result->races);
        $this->assertCount(2, $result->races->first()->positions);

        foreach ([$driverOne, $driverTwo] as $driver) {
            $this->get(route('teams.esports.show', $driver))
                ->assertOk()
                ->assertSee('XCL Endurance Round 1')
                ->assertSee('Spa-Francorchamps');
        }
    }

    public function test_updating_a_result_replaces_its_races_and_positions(): void
    {
        $manager = $this->esportsManager();
        $driver = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'lmu', 'sort_order' => 1]);

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'lmu-team',
            'event_date' => '2026-03-01',
            'track' => 'Le Mans',
            'driver_positions' => [$driver->id => 'P5'],
        ]);

        $result = Result::where('subject', 'lmu-team')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.results.update', $result), [
            'subject' => 'lmu-team',
            'event_date' => '2026-03-01',
            'track' => 'Sebring',
            'driver_positions' => [$driver->id => 'P1'],
        ])->assertRedirect(route('admin.results.index'));

        $result->refresh();
        $this->assertCount(1, $result->races);
        $this->assertSame('Sebring', $result->races->first()->track);
        $this->assertSame('P1', $result->races->first()->positions->first()->position);
    }

    public function test_deleting_a_result_cascades_to_its_races_and_positions(): void
    {
        $manager = $this->esportsManager();
        $driver = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'iracing', 'sort_order' => 1]);

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'iracing-team',
            'event_date' => '2026-01-15',
            'track' => 'Daytona',
            'driver_positions' => [$driver->id => 'P3'],
        ]);

        $result = Result::where('subject', 'iracing-team')->firstOrFail();
        $raceId = $result->races->first()->id;

        $this->actingAs($manager)->delete(route('admin.results.destroy', $result))
            ->assertRedirect();

        $this->assertDatabaseMissing('results', ['id' => $result->id]);
        $this->assertDatabaseMissing('result_races', ['id' => $raceId]);
    }
}
