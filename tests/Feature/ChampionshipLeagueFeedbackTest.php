<?php

namespace Tests\Feature;

use App\Http\Controllers\ChampionshipController;
use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback (NLRL, 2026-09): driver-swap championships are team-only, the
// team sign-up's car is picked from ACC's catalogue, and rounds can limit tyre sets.
class ChampionshipLeagueFeedbackTest extends TestCase
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

    private function makeChampionship(array $format = [], array $overrides = []): Championship
    {
        $championship = Championship::create(array_merge([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['format' => $format]);
        $championship->save();

        return $championship;
    }

    public function test_a_driver_swaps_championship_only_takes_teams_and_spectators(): void
    {
        $championship = $this->makeChampionship(['driver_swaps_enabled' => true, 'spectator_slots' => 2]);
        $driver = User::factory()->create();

        $this->actingAs($driver)->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('This is a team championship')
            ->assertDontSee('Just me (no team)');

        $this->actingAs($driver)->post(route('championships.register', $championship))
            ->assertSessionHas('error', ChampionshipController::ERR_TEAM_ONLY);
        $this->assertFalse($championship->fresh()->isRegistered($driver));

        $this->actingAs($driver)->post(route('championships.register', $championship), ['is_spectator' => 1])
            ->assertSessionHas('success');
    }

    public function test_the_team_car_is_picked_from_the_acc_catalogue_of_the_right_class(): void
    {
        $championship = $this->makeChampionship(
            ['driver_swaps_enabled' => true, 'team_registration_scope' => 'championship'],
            ['is_multiclass' => true]
        );
        $gt3 = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'GT3', 'car_class' => 'GT3']);

        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);

        $this->actingAs($owner)->get(route('championships.show', $championship))
            ->assertOk()
            ->assertSee('data-car-select', false)
            ->assertSee('Ferrari 488 GT3 (2018)');

        $register = fn (string $car) => $this->actingAs($owner)->post(route('championships.register', $championship), [
            'racing_team_id' => $team->id, 'driver_ids' => [$owner->id], 'car_number' => 7,
            'starting_driver_id' => $owner->id, 'car_model' => $car, 'championship_class_id' => $gt3->id,
        ]);

        $register('My Homemade Car')->assertSessionHasErrors('car_model');
        $register('Porsche 991 II GT3 Cup (2017)')
            ->assertSessionHas('error', "The Porsche 991 II GT3 Cup (2017) isn't a GT3 car.");
        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id]);

        $register('Ferrari 488 GT3 (2018)')->assertSessionHas('success');
        $this->assertDatabaseHas('championship_registrations', [
            'championship_id' => $championship->id, 'car_model' => 'Ferrari 488 GT3 (2018)',
        ]);
    }

    public function test_round_tyre_set_count_is_saved_and_pushed_as_event_rules(): void
    {
        $championship = $this->makeChampionship([], ['status' => 'draft']);
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->actingAs($manager->refresh())
            ->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
                'track' => 'monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i'),
                'tyre_set_count' => 3,
            ])
            ->assertSessionHasNoErrors();

        $round = $championship->rounds()->firstOrFail();
        $this->assertSame(3, (int) $round->tyre_set_count);

        $config = app(AccServerConfigService::class);
        $this->assertSame(3, $config->eventRules($round)['tyreSetCount']);

        $round->update(['tyre_set_count' => null]);
        $this->assertArrayNotHasKey('tyreSetCount', $config->eventRules($round->fresh()));
    }

    public function test_race_page_car_options_use_the_platforms_catalogue(): void
    {
        $pc = Race::create([
            'title' => 'PC Race', 'track' => 'monza', 'game' => 'ac', 'car_class' => 'GT4',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);

        $options = $pc->carOptions();
        $this->assertNotEmpty($options);
        $this->assertTrue($options->every(fn ($name) => AccCarCatalog::classOfName($name, 'ac') === 'GT4'));
    }
}
