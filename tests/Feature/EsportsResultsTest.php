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
            'type' => 'race',
            'event_date' => '2026-05-10',
            'title' => 'XCL Endurance Round 1',
            'track' => 'Spa-Francorchamps',
            'car_class' => 'GT3',
            'selected_drivers' => [$driverOne->id, $driverTwo->id],
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

    public function test_championship_standings_and_final_results_show_points_on_the_drivers_page(): void
    {
        $manager = $this->esportsManager();
        $one = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'acc', 'sort_order' => 1]);
        $two = EsportsDriver::create(['name' => 'Sam Speed', 'slug' => 'sam-speed', 'game' => 'acc', 'sort_order' => 2]);

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'acc-team',
            'type' => 'standings',
            'event_date' => '2026-06-01',
            'title' => 'XCL Sprint Series',
            'round_label' => 'After round 4 of 8',
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'P2', $two->id => 'P1'],
            'driver_points' => [$one->id => '88', $two->id => '95'],
        ])->assertRedirect(route('admin.results.index'));

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'acc-team',
            'type' => 'final',
            'event_date' => '2026-12-01',
            'title' => 'XCL Sprint Series',
            'selected_drivers' => [$one->id],
            'driver_positions' => [$one->id => 'P1'],
            'driver_points' => [$one->id => '210'],
        ])->assertRedirect(route('admin.results.index'));

        $this->assertDatabaseHas('results', ['type' => 'standings', 'round_label' => 'After round 4 of 8']);
        $this->assertDatabaseHas('results', ['type' => 'final']);

        $this->get(route('teams.esports.show', $one))
            ->assertOk()
            ->assertSee('LIVE STANDINGS')
            ->assertSee('After round 4 of 8')
            ->assertSee('FINAL RESULT')
            ->assertSee('88')
            ->assertSee('210');
    }

    public function test_a_standings_result_requires_a_championship_name_but_no_track(): void
    {
        $manager = $this->esportsManager();
        $driver = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'acc', 'sort_order' => 1]);

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'acc-team',
            'type' => 'standings',
            'event_date' => '2026-06-01',
            'selected_drivers' => [$driver->id],
            'driver_positions' => [$driver->id => 'P1'],
        ])->assertSessionHasErrors('title');

        $this->assertSame(0, Result::where('category', 'esports')->count());
    }

    private function twoAccDrivers(): array
    {
        return [
            EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'acc', 'sort_order' => 1]),
            EsportsDriver::create(['name' => 'Sam Speed', 'slug' => 'sam-speed', 'game' => 'acc', 'sort_order' => 2]),
        ];
    }

    private function racePayload(array $overrides): array
    {
        return array_merge([
            'subject' => 'acc-team',
            'type' => 'race',
            'event_date' => '2026-05-10',
            'track' => 'Monza',
        ], $overrides);
    }

    public function test_positions_sent_for_unselected_drivers_are_discarded(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id],
            'driver_positions' => [$one->id => 'P1', $two->id => 'P2'],
        ]))->assertRedirect(route('admin.results.index'));

        $positions = Result::where('subject', 'acc-team')->firstOrFail()->races->first()->positions;
        $this->assertCount(1, $positions);
        $this->assertSame($one->id, $positions->first()->esports_driver_id);
    }

    public function test_every_selected_driver_needs_a_position(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'P1', $two->id => ''],
        ]))->assertSessionHasErrors(['driver_positions' => 'Enter a position for Sam Speed.']);

        $this->assertSame(0, Result::where('category', 'esports')->count());
    }

    public function test_at_least_one_driver_must_be_selected(): void
    {
        [$one] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'driver_positions' => [$one->id => 'P1'],
        ]))->assertSessionHasErrors('selected_drivers');
    }

    public function test_two_selected_drivers_cannot_share_a_position(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'P3', $two->id => 'p3'],
        ]))->assertSessionHasErrors(['driver_positions' => 'Alex Rider and Sam Speed cannot both have position p3 unless they share the same car number.']);

        $this->assertSame(0, Result::where('category', 'esports')->count());
    }

    public function test_drivers_in_the_same_car_can_share_a_position(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'P3', $two->id => 'P3'],
            'driver_cars' => [$one->id => 'Ferrari 296 GT3', $two->id => 'Ferrari 296 GT3'],
            'driver_car_numbers' => [$one->id => '787', $two->id => '787'],
        ]))->assertRedirect(route('admin.results.index'));

        $positions = Result::where('category', 'esports')->firstOrFail()->races->first()->positions;
        $this->assertCount(2, $positions);
        $this->assertSame(['787', '787'], $positions->pluck('car_number')->all());
        $this->assertSame('Ferrari 296 GT3', $positions->first()->car);
    }

    public function test_different_car_numbers_still_cannot_share_a_position(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'P3', $two->id => 'P3'],
            'driver_car_numbers' => [$one->id => '787', $two->id => '788'],
        ]))->assertSessionHasErrors('driver_positions');

        $this->assertSame(0, Result::where('category', 'esports')->count());
    }

    public function test_unclassified_results_such_as_dnf_can_be_shared(): void
    {
        [$one, $two] = $this->twoAccDrivers();

        $this->actingAs($this->esportsManager())->post(route('admin.results.store'), $this->racePayload([
            'selected_drivers' => [$one->id, $two->id],
            'driver_positions' => [$one->id => 'DNF', $two->id => 'DNF'],
        ]))->assertRedirect(route('admin.results.index'));
    }

    public function test_driver_rows_are_revealed_by_a_css_rule_per_driver(): void
    {
        [$one] = $this->twoAccDrivers();

        $html = $this->actingAs($this->esportsManager())->get(route('admin.results.index'))->getContent();

        $this->assertStringContainsString("form:has(#driver-cb-{$one->id}:checked) [data-result-row=\"{$one->id}\"]{display:flex}", $html);
        $this->assertStringContainsString('[data-result-row]{display:none}', $html);
        $this->assertStringContainsString('Select drivers above to enter their results', $html);
    }

    public function test_updating_a_result_replaces_its_races_and_positions(): void
    {
        $manager = $this->esportsManager();
        $driver = EsportsDriver::create(['name' => 'Alex Rider', 'slug' => 'alex-rider', 'game' => 'lmu', 'sort_order' => 1]);

        $this->actingAs($manager)->post(route('admin.results.store'), [
            'subject' => 'lmu-team',
            'type' => 'race',
            'event_date' => '2026-03-01',
            'track' => 'Le Mans',
            'selected_drivers' => [$driver->id],
            'driver_positions' => [$driver->id => 'P5'],
        ]);

        $result = Result::where('subject', 'lmu-team')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.results.update', $result), [
            'subject' => 'lmu-team',
            'type' => 'race',
            'event_date' => '2026-03-01',
            'track' => 'Sebring',
            'selected_drivers' => [$driver->id],
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
            'type' => 'race',
            'event_date' => '2026-01-15',
            'track' => 'Daytona',
            'selected_drivers' => [$driver->id],
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
