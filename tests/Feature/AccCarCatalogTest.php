<?php

namespace Tests\Feature;

use App\Models\Bop;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\Role;
use App\Models\User;
use App\Services\AccCarCatalog;
use App\Services\AccResultImportService;
use App\Services\AccServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ACC console ('acc') and ACC PC ('ac') number their cars differently, so every
// ID <-> name lookup has to use the catalogue of the race/BOP's own game.
class AccCarCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_same_car_has_a_different_id_per_platform(): void
    {
        $this->assertSame(26, AccCarCatalog::id('BMW M4 GT3 (2022)', 'acc'));
        $this->assertSame(30, AccCarCatalog::id('BMW M4 GT3 (2022)', 'ac'));

        $this->assertSame('BMW M4 GT3 (2022)', AccCarCatalog::name(26, 'acc'));
        $this->assertSame('Ferrari 488 Challenge Evo (2022)', AccCarCatalog::name(26, 'ac'));
    }

    public function test_bop_push_uses_the_car_ids_of_its_own_platform(): void
    {
        Bop::create(['game' => 'acc', 'car_model' => 'BMW M4 GT3 (2022)', 'ballast_kg' => 10, 'restrictor' => 0]);
        Bop::create(['game' => 'ac', 'car_model' => 'BMW M4 GT3 (2022)', 'ballast_kg' => 5, 'restrictor' => 0]);
        Bop::create(['game' => 'acc', 'car_model' => 'Not A Real Car', 'ballast_kg' => 10, 'restrictor' => 0]);

        $config = app(AccServerConfigService::class);

        $this->assertSame([26], array_column($config->bop('acc')['entries'], 'carModel'));
        $this->assertSame([30], array_column($config->bop('ac')['entries'], 'carModel'));
    }

    public function test_result_import_names_cars_from_the_races_platform(): void
    {
        $race = Race::create([
            'title' => 'PC Race', 'track' => 'Monza', 'game' => 'ac',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $user = User::factory()->create(['platform' => 'steam', 'platform_id' => 'S76561198000000001']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);

        $content = json_encode([
            'sessionType' => 'Q',
            'sessionResult' => ['bestlap' => 100000, 'leaderBoardLines' => [[
                'car' => ['raceNumber' => 7, 'carModel' => 30, 'drivers' => [
                    ['playerId' => 'S76561198000000001', 'lastName' => 'Driver'],
                ]],
                'timing' => ['bestLap' => 100000, 'lapCount' => 5, 'totalTime' => 500000],
            ]]],
        ]);

        app(AccResultImportService::class)->processSessions($content, $race, 'test.json');

        $result = RaceResult::where('race_id', $race->id)->sole();
        $this->assertSame('BMW M4 GT3 (2022)', $result->vehicle);
        $this->assertSame('GT3', $result->car_class);
    }

    public function test_bop_form_rejects_a_car_name_outside_the_games_catalogue(): void
    {
        $owner = User::factory()->create();
        $owner->roles()->attach(Role::where('slug', 'owner')->first());

        $this->actingAs($owner)->get(route('admin.bops.create'))
            ->assertOk()
            ->assertSee('id="bop-cars-acc"', false)
            ->assertSee('id="bop-cars-ac"', false);

        $payload = ['game' => 'ac', 'ballast_kg' => 10, 'restrictor' => 0];

        $this->actingAs($owner)
            ->post(route('admin.bops.store'), $payload + ['car_model' => 'BMW M4 GT3'])
            ->assertSessionHasErrors('car_model');

        $this->actingAs($owner)
            ->post(route('admin.bops.store'), $payload + ['car_model' => 'BMW M4 GT3 (2022)'])
            ->assertSessionHasNoErrors();

        // Games without a catalogue keep free-text car names.
        $this->actingAs($owner)
            ->post(route('admin.bops.store'), ['game' => 'lmu', 'car_model' => 'Anything', 'ballast_kg' => 0, 'restrictor' => 0])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Bop::count());
    }

    public function test_public_bop_category_filter_uses_the_games_catalogue(): void
    {
        Bop::create(['game' => 'ac', 'car_model' => 'BMW M4 GT3 (2022)', 'ballast_kg' => 10, 'restrictor' => 0]);
        Bop::create(['game' => 'ac', 'car_model' => 'Alpine A110 GT4 (2018)', 'ballast_kg' => 10, 'restrictor' => 0]);

        $this->get(route('bop.index', ['game' => 'ac', 'category' => 'gt3']))
            ->assertOk()
            ->assertSee('BMW M4 GT3 (2022)')
            ->assertDontSee('Alpine A110 GT4 (2018)');
    }
}
