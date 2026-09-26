<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback (NLRL, 2026-09): a team can enter several cars in a driver-swap
// championship, up to settings.format.max_cars_per_team. A driver is only ever in
// one car, and each car scores on its own — no team totals.
class ChampionshipMultiCarTeamTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private array $drivers;

    private RacingTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->drivers = User::factory()->count(4)->create()->all();
        $this->team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $this->owner->id]);
        $this->team->members()->attach(collect($this->drivers)->pluck('id'));
    }

    private function makeChampionship(string $scope, int $maxCars = 2): Championship
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => [
                'driver_swaps_enabled' => true, 'team_registration_scope' => $scope,
                'max_drivers_per_car' => 2, 'max_cars_per_team' => $maxCars,
            ],
            'scoring' => ['team_points_enabled' => true],
        ]);
        $championship->save();

        return $championship;
    }

    private function makeRound(Championship $championship): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => 1,
            'title' => 'Round 1', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
    }

    // Car n (0-based) is driven by drivers 2n and 2n+1, starting with the first.
    private function registerCar(Championship $championship, array $driverIndexes, array $extra = [])
    {
        $ids = array_map(fn ($i) => $this->drivers[$i]->id, $driverIndexes);

        return $this->actingAs($this->owner)->post(route('championships.register', $championship), array_merge([
            'racing_team_id' => $this->team->id, 'driver_ids' => $ids, 'starting_driver_id' => $ids[0],
        ], $extra));
    }

    public function test_a_team_enters_cars_up_to_the_maximum_and_each_goes_into_every_round(): void
    {
        $championship = $this->makeChampionship('championship');
        $round = $this->makeRound($championship);

        $this->registerCar($championship, [0, 1], ['car_number' => 7])->assertSessionHas('success');
        $this->registerCar($championship, [2, 3], ['car_number' => 8])->assertSessionHas('success');

        $this->assertSame(2, $round->teamEntries()->count());
        $this->assertSame(4, $round->registrations()->count());

        $this->registerCar($championship, [0, 1], ['car_number' => 9])
            ->assertSessionHas('error', 'Your team already has the maximum of 2 cars in this championship.');
    }

    public function test_the_default_of_one_car_keeps_the_old_message(): void
    {
        $championship = $this->makeChampionship('per_round', maxCars: 1);

        $this->registerCar($championship, [0, 1])->assertSessionHas('success');
        $this->registerCar($championship, [2, 3])
            ->assertSessionHas('error', 'Your team is already registered for this championship.');
    }

    public function test_a_driver_can_only_be_in_one_car(): void
    {
        $championship = $this->makeChampionship('per_round');

        $this->registerCar($championship, [0, 1])->assertSessionHas('success');
        $this->registerCar($championship, [1, 2])->assertSessionHas('error');

        $this->assertSame(1, $championship->registrations()->count());
    }

    public function test_two_cars_cannot_share_a_number(): void
    {
        $championship = $this->makeChampionship('championship');

        $this->registerCar($championship, [0, 1], ['car_number' => 7])->assertSessionHas('success');
        $this->registerCar($championship, [2, 3], ['car_number' => 7])
            ->assertSessionHas('error', 'Car number #7 is already taken in this championship.');
    }

    public function test_per_round_teams_sign_up_one_round_entry_per_registered_car(): void
    {
        $championship = $this->makeChampionship('per_round');
        $round = $this->makeRound($championship);
        $this->registerCar($championship, [0, 1])->assertSessionHas('success');
        $this->registerCar($championship, [2, 3])->assertSessionHas('success');

        $signUp = fn (int $carNumber, array $driverIndexes) => $this->actingAs($this->owner)->post(route('events.register-team', $round), [
            'car_number' => $carNumber,
            'driver_ids' => array_map(fn ($i) => $this->drivers[$i]->id, $driverIndexes),
            'starting_driver_id' => $this->drivers[$driverIndexes[0]]->id,
        ]);

        $signUp(7, [0, 1])->assertSessionMissing('error');

        // The second car's line-up is the one pre-selected next.
        $this->actingAs($this->owner)->get(route('events.show', $round))
            ->assertOk()
            ->assertViewHas('canAddChampionshipCar', true)
            ->assertViewHas('preselectedDriverIds', [$this->drivers[2]->id, $this->drivers[3]->id]);

        $signUp(8, [2, 3])->assertSessionMissing('error');
        $this->assertSame(2, $round->teamEntries()->count());

        $this->actingAs($this->owner)->get(route('events.show', $round))->assertViewHas('canAddChampionshipCar', false);
    }

    public function test_withdrawing_one_car_keeps_the_other(): void
    {
        $championship = $this->makeChampionship('per_round');
        $this->registerCar($championship, [0, 1]);
        $this->registerCar($championship, [2, 3]);
        $first = $championship->registrations()->orderBy('id')->first();

        $this->actingAs($this->owner)
            ->delete(route('championships.unregister', $championship), ['registration_id' => $first->id])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('championship_registrations', ['id' => $first->id]);
        $this->assertSame(1, $championship->registrations()->count());
    }

    // With room for a second car the page shows the sign-up form plus the car list,
    // not the "registered" block with its Unregister button — the team's only car
    // still needs a way out there.
    public function test_a_teams_only_car_can_be_withdrawn_while_it_may_add_more(): void
    {
        $championship = $this->makeChampionship('per_round');
        $this->registerCar($championship, [0, 1]);

        $this->actingAs($this->owner)->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('Add Car')
            ->assertSee('Withdraw');
    }

    public function test_team_standings_list_each_car_separately(): void
    {
        $championship = $this->makeChampionship('per_round');
        $this->registerCar($championship, [0, 1]);
        $this->registerCar($championship, [2, 3]);

        $standings = $championship->fresh()->computeTeamStandings();

        $this->assertCount(2, $standings);
        $this->assertSame(['Car 1', 'Car 2'], collect($standings)->pluck('car_label')->sort()->values()->all());
    }
}
