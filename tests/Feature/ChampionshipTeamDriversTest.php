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

// League feedback (NLRL): a team registering for a driver-swaps championship picks
// which of its members drive the car. "championship" scope enters exactly those into
// every round; "per_round" scope signs up per round, starting from that line-up.
class ChampionshipTeamDriversTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $anna;

    private User $bob;

    private RacingTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->anna = User::factory()->create();
        $this->bob = User::factory()->create();
        $this->team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $this->owner->id]);
        $this->team->members()->attach([$this->anna->id, $this->bob->id]);
    }

    private function makeChampionship(string $scope): Championship
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
                'min_drivers_per_car' => 2, 'max_drivers_per_car' => 2,
            ],
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

    private function registerTeam(Championship $championship, array $driverIds, array $extra = [])
    {
        return $this->actingAs($this->owner)->post(route('championships.register', $championship), array_merge([
            'racing_team_id' => $this->team->id, 'driver_ids' => $driverIds,
        ], $extra));
    }

    public function test_championship_scope_enters_only_the_picked_drivers_into_every_round(): void
    {
        $championship = $this->makeChampionship('championship');
        $round = $this->makeRound($championship);

        $this->registerTeam($championship, [$this->anna->id, $this->bob->id], [
            'car_number' => 7, 'starting_driver_id' => $this->anna->id,
        ])->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            [$this->anna->id, $this->bob->id],
            $round->registrations()->pluck('user_id')->all()
        );
    }

    public function test_the_starting_driver_must_be_a_picked_driver(): void
    {
        $championship = $this->makeChampionship('championship');

        $this->registerTeam($championship, [$this->anna->id, $this->bob->id], [
            'car_number' => 7, 'starting_driver_id' => $this->owner->id,
        ])->assertSessionHas('error', 'The starting driver must be one of the selected drivers.');

        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id]);
    }

    public function test_the_line_up_must_fit_the_drivers_per_car_bounds_and_the_team(): void
    {
        $championship = $this->makeChampionship('per_round');

        $this->registerTeam($championship, [$this->anna->id])
            ->assertSessionHas('error', 'Pick at least 2 driver(s) for your car.');
        $this->registerTeam($championship, [$this->owner->id, $this->anna->id, $this->bob->id])
            ->assertSessionHas('error', 'Pick at most 2 driver(s) for your car.');
        $this->registerTeam($championship, [$this->anna->id, User::factory()->create()->id])
            ->assertSessionHas('error', 'Every driver must be a member of your team.');

        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id]);
    }

    public function test_per_round_scope_signs_up_per_round_starting_from_the_picked_line_up(): void
    {
        $championship = $this->makeChampionship('per_round');
        $round = $this->makeRound($championship);

        $signUp = fn () => $this->actingAs($this->owner)->post(route('events.register-team', $round), [
            'car_number' => 7, 'driver_ids' => [$this->anna->id, $this->bob->id], 'starting_driver_id' => $this->bob->id,
        ]);

        $signUp()->assertSessionHas('error', 'Register your team for the championship first.');

        $this->actingAs($this->owner)->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('name="driver_ids[]"', false)
            ->assertSee($this->anna->displayName())
            ->assertSee('(pick 2)');

        $this->registerTeam($championship, [$this->anna->id, $this->bob->id])->assertSessionHas('success');
        $this->assertDatabaseMissing('race_team_entries', ['race_id' => $round->id]);

        $this->actingAs($this->owner)->get(route('events.show', $round))
            ->assertOk()
            ->assertViewHas('preselectedDriverIds', [$this->anna->id, $this->bob->id])
            ->assertSee('REGISTER TEAM');

        $signUp()->assertSessionMissing('error');
        $this->assertDatabaseHas('race_team_entries', [
            'race_id' => $round->id, 'racing_team_id' => $this->team->id, 'starting_driver_id' => $this->bob->id,
        ]);
    }

    public function test_championship_scope_rounds_cannot_be_signed_up_for_by_hand(): void
    {
        $championship = $this->makeChampionship('championship');
        $round = $this->makeRound($championship);

        $this->actingAs($this->owner)->post(route('events.register-team', $round), [
            'car_number' => 7, 'driver_ids' => [$this->anna->id, $this->bob->id], 'starting_driver_id' => $this->anna->id,
        ])->assertSessionHas('error', 'Your team is entered into this round automatically from its championship registration.');
    }
}
