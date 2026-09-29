<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A solo driver picks car and number when registering for an ACC championship;
// every round forces that car, and only the league can change it afterwards.
class ChampionshipSoloCarTest extends TestCase
{
    use RefreshDatabase;

    private const GT3_CAR = 'Ferrari 296 GT3 (2023)';

    private const GT4_CAR = 'BMW M4 GT4 (2018)';

    private League $league;

    private Championship $championship;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->championship = Championship::create([
            'league_id' => $this->league->id, 'name' => 'GT3 Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'car_class' => 'GT3',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function register(User $user, array $data)
    {
        return $this->actingAs($user)->post(route('championships.register', $this->championship), $data);
    }

    public function test_a_solo_driver_registers_with_car_and_number(): void
    {
        $driver = User::factory()->create();

        $this->register($driver, ['car_model' => self::GT3_CAR, 'car_number' => 44])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $registration = ChampionshipRegistration::where('user_id', $driver->id)->sole();
        $this->assertSame(self::GT3_CAR, $registration->car_model);
        $this->assertSame(44, (int) $registration->car_number);
    }

    public function test_car_and_number_are_required_the_car_must_fit_the_class_and_the_number_be_free(): void
    {
        $this->register(User::factory()->create(), [])
            ->assertSessionHasErrors(['car_model', 'car_number']);

        $this->register(User::factory()->create(), ['car_model' => self::GT4_CAR, 'car_number' => 5])
            ->assertSessionHas('error', 'The '.self::GT4_CAR.' isn\'t a GT3 car.');

        $this->register(User::factory()->create(), ['car_model' => self::GT3_CAR, 'car_number' => 7])->assertSessionHas('success');
        $this->register(User::factory()->create(), ['car_model' => self::GT3_CAR, 'car_number' => 7])
            ->assertSessionHas('error', 'Car number #7 is already taken in this championship.');

        $this->assertSame(1, $this->championship->registrations()->count());
    }

    public function test_every_round_forces_the_picked_car_and_number(): void
    {
        $picked = User::factory()->create(['platform_id' => 'X-1', 'car_number' => 12]);
        $other = User::factory()->create(['platform_id' => 'X-2', 'car_number' => 13]);
        $this->register($picked, ['car_model' => self::GT3_CAR, 'car_number' => 44])->assertSessionHas('success');

        $round = Race::create([
            'championship_id' => $this->championship->id, 'round_number' => 1, 'title' => 'Round 1',
            'track' => 'Monza', 'game' => 'acc', 'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        RaceRegistration::create(['race_id' => $round->id, 'user_id' => $picked->id]);
        RaceRegistration::create(['race_id' => $round->id, 'user_id' => $other->id]);

        $entries = collect(app(AccServerConfigService::class)->entryList($round)['entries'])
            ->keyBy(fn ($entry) => $entry['drivers'][0]['playerID']);

        $this->assertSame(AccCarCatalog::id(self::GT3_CAR, 'acc'), $entries['X-1']['forcedCarModel']);
        $this->assertSame(44, $entries['X-1']['raceNumber']);
        // Not registered for the championship with a car: free choice, profile number.
        $this->assertSame(-1, $entries['X-2']['forcedCarModel']);
        $this->assertSame(13, $entries['X-2']['raceNumber']);
    }

    public function test_only_the_league_changes_a_solo_car(): void
    {
        $driver = User::factory()->create();
        $this->register($driver, ['car_model' => self::GT3_CAR, 'car_number' => 44])->assertSessionHas('success');
        $registration = ChampionshipRegistration::where('user_id', $driver->id)->sole();
        $newCar = 'BMW M4 GT3 (2022)';

        // The driver has no way in (league.access hides the admin area: 404).
        $this->actingAs($driver)
            ->put(route('admin.leagues.championships.entries.car', [$this->league, $this->championship, $registration]), ['car_model' => $newCar, 'car_number' => 1])
            ->assertNotFound();
        $this->assertSame(self::GT3_CAR, $registration->fresh()->car_model);

        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();

        $this->actingAs($manager->refresh())
            ->put(route('admin.leagues.championships.entries.car', [$this->league, $this->championship, $registration]), ['car_model' => $newCar, 'car_number' => 1])
            ->assertSessionHas('success');

        $registration->refresh();
        $this->assertSame($newCar, $registration->car_model);
        $this->assertSame(1, (int) $registration->car_number);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.entries.index', [$this->league, $this->championship]))
            ->assertOk()
            ->assertSee($newCar);
    }

    public function test_registration_form_offers_only_the_class_cars(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('championships.show', $this->championship))
            ->assertOk()
            ->assertSee('name="car_number"', false)
            ->assertSee(self::GT3_CAR)
            ->assertDontSee(self::GT4_CAR);
    }
}
