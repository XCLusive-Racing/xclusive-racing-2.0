<?php

namespace Tests\Feature;

use App\Models\TimeTrialCar;
use App\Models\TimeTrialLap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrialsTest extends TestCase
{
    use RefreshDatabase;

    private string $lapsCsv;

    private string $carsCsv;

    protected function setUp(): void
    {
        parent::setUp();

        // Same shape as the real export: spaced header, trailing empty column.
        $this->lapsCsv = $this->csv(<<<'CSV'
Track, DriverID, DriverName, BestLap, Car, CarClass, S1, S2, S3, Patch, Event, NoOfLaps,
brands_hatch,M111,Alpha,1:22.312,35,GT3,26.547,21.31,34.455,v1.9.5,27,167,
brands_hatch,M111,Alpha,1:22.355,35,GT3,26.612,21.37,34.372,v1.9.5,27,167,
brands_hatch,P222,Bravo,1:22.457,32,GT3,26.63,21.43,34.397,v1.9.12,151,129,
brands_hatch,M111,Alpha,1:23.000,32,GT3,26.9,21.5,34.6,v1.9.12,77,10,
brands_hatch,M333,Charlie,1:31.000,61,GT4,30.0,25.0,36.5,v1.9.12,77,10,
brands_hatch,X444,Xray,1:20.000,35,GT3,26.0,21.0,33.0,v1.9.12,77,10,
nurburgring_24h,M111,Alpha,8:04.272,32,GT3,167.66,151.262,165.35,v1.9.12,0,72,
CSV);

        $this->carsCsv = $this->csv(<<<'CSV'
CarID,CarName,Year,Logo
32,Ferrari 296 GT3,2023,https://static.wixstatic.com/a.png
35,McLaren 720S GT3 Evo,2023,https://static.wixstatic.com/b.png
61,Porsche 718 Cayman GT4 Clubsport,2019,https://static.wixstatic.com/c.png
CSV);
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tt');
        file_put_contents($path, $content);

        return $path;
    }

    private function import(bool $confirm = true)
    {
        return $this->artisan('time-trials:import', array_filter([
            'laps' => $this->lapsCsv,
            'cars' => $this->carsCsv,
            '--confirm' => $confirm,
        ]));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->import(false)
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertSame(0, TimeTrialLap::count());
        $this->assertSame(0, TimeTrialCar::count());
    }

    public function test_import_stores_integer_milliseconds_and_derives_the_platform(): void
    {
        $this->import()->assertSuccessful();

        // The X-prefixed identifier is rejected, everything else kept, repeats included.
        $this->assertSame(6, TimeTrialLap::count());
        $this->assertFalse(TimeTrialLap::where('platform_identifier', 'X444')->exists());

        $lap = TimeTrialLap::where('platform_identifier', 'M111')->where('lap_time_ms', 82312)->firstOrFail();
        $this->assertSame('xbox', $lap->platform);
        $this->assertSame([26547, 21310, 34455], [$lap->sector1_ms, $lap->sector2_ms, $lap->sector3_ms]);
        $this->assertSame(27, $lap->source_event);
        $this->assertSame(167, $lap->laps_driven);
        $this->assertSame('import', $lap->source);
        $this->assertNull($lap->recorded_at);

        $this->assertSame('playstation', TimeTrialLap::where('platform_identifier', 'P222')->value('platform'));
        $this->assertSame(484272, TimeTrialLap::where('track', 'nurburgring_24h')->value('lap_time_ms'));
    }

    public function test_cars_are_keyed_on_id_mapped_to_acc_and_logos_dropped(): void
    {
        $this->import()->assertSuccessful();

        $car = TimeTrialCar::findOrFail(35);
        $this->assertSame('McLaren 720S GT3 Evo', $car->name);
        $this->assertSame(2023, $car->year);
        $this->assertSame(35, $car->acc_car_model);
        $this->assertArrayNotHasKey('logo', $car->getAttributes());
    }

    public function test_repeat_laps_are_kept_and_the_fastest_is_the_personal_best(): void
    {
        $this->import()->assertSuccessful();

        $rows = TimeTrialLap::where('platform_identifier', 'M111')->where('track', 'brands_hatch')->where('car_id', 35)
            ->orderBy('lap_time_ms')->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows[0]->is_personal_best);
        $this->assertFalse($rows[1]->is_personal_best);
    }

    public function test_import_is_idempotent(): void
    {
        $this->import()->assertSuccessful();
        $this->import()->assertSuccessful();

        $this->assertSame(6, TimeTrialLap::count());
        $this->assertSame(1, TimeTrialLap::where('platform_identifier', 'M111')->where('car_id', 35)->where('is_personal_best', true)->count());
    }

    public function test_laps_link_to_a_member_by_platform_identifier_without_creating_accounts(): void
    {
        $member = User::factory()->create(['platform_id' => 'P222']);
        $users = User::count();

        $this->import()->assertSuccessful();

        $this->assertSame($member->id, TimeTrialLap::where('platform_identifier', 'P222')->value('user_id'));
        $this->assertNull(TimeTrialLap::where('platform_identifier', 'M111')->value('user_id'));
        $this->assertSame('Alpha', TimeTrialLap::where('platform_identifier', 'M111')->value('driver_name'));
        $this->assertSame($users, User::count());
    }

    public function test_999_placeholder_sectors_are_stored_as_empty(): void
    {
        $this->lapsCsv = $this->csv(<<<'CSV'
Track, DriverID, DriverName, BestLap, Car, CarClass, S1, S2, S3, Patch, Event, NoOfLaps,
watkins_glen,M555,Flitzeflopp,1:45.587,32,GT3,999,999,999,v1.9.12,72,29,
CSV);

        $this->import()->assertSuccessful();

        $lap = TimeTrialLap::where('platform_identifier', 'M555')->firstOrFail();
        $this->assertSame(105587, $lap->lap_time_ms);
        $this->assertNull($lap->sector1_ms);
        $this->assertNull($lap->sector2_ms);
        $this->assertNull($lap->sector3_ms);
    }

    public function test_lap_and_sector_formatting(): void
    {
        $this->assertSame('1:22.312', TimeTrialLap::formatLap(82312));
        $this->assertSame('8:04.272', TimeTrialLap::formatLap(484272));
        $this->assertSame('21.310', TimeTrialLap::formatSector(21310));
        $this->assertSame('2:47.660', TimeTrialLap::formatSector(167660));
        $this->assertSame('+0.043', TimeTrialLap::formatGap(43));
        $this->assertSame('-0.100', TimeTrialLap::formatGap(-100));
    }

    public function test_track_page_shows_one_best_lap_per_driver_per_car_fastest_first(): void
    {
        $this->import()->assertSuccessful();

        $response = $this->get(route('time-trials.show', 'brands_hatch'))->assertOk();

        $response->assertSeeInOrder(['1:22.312', '1:22.457', '1:23.000', '1:31.000']);
        $response->assertDontSee('1:22.355'); // Alpha's slower lap in the same car
        $response->assertSee('Theoretical best');
        $response->assertSee('in game ACC balance');
    }

    public function test_class_and_car_filters(): void
    {
        $this->import()->assertSuccessful();

        $this->get(route('time-trials.show', ['track' => 'brands_hatch', 'class' => 'GT4']))
            ->assertOk()->assertSee('1:31.000')->assertDontSee('1:22.312');

        $this->get(route('time-trials.show', ['track' => 'brands_hatch', 'car' => 32]))
            ->assertOk()->assertSee('1:22.457')->assertSee('1:23.000')->assertDontSee('1:22.312');
    }

    // Car ID 0 (Porsche 991 GT3 R) is a real car: without ?car the page shows every car,
    // it must not fall back to filtering on car 0.
    public function test_track_page_defaults_to_all_cars_even_with_car_id_zero(): void
    {
        $this->lapsCsv = $this->csv(<<<'CSV'
Track, DriverID, DriverName, BestLap, Car, CarClass, S1, S2, S3, Patch, Event, NoOfLaps,
brands_hatch,M111,Alpha,1:22.312,35,GT3,26.547,21.31,34.455,v1.9.5,27,167,
brands_hatch,M222,Porsche Pilot,1:24.393,0,GT3,27.0,21.9,35.493,v1.9.12,77,10,
CSV);
        $this->carsCsv = $this->csv(<<<'CSV'
CarID,CarName,Year,Logo
0,Porsche 991 GT3 R,2018,x
35,McLaren 720S GT3 Evo,2023,x
CSV);

        $this->import()->assertSuccessful();

        $this->get(route('time-trials.show', 'brands_hatch'))
            ->assertOk()->assertSeeInOrder(['Alpha', 'Porsche Pilot']);

        $this->get(route('time-trials.show', ['track' => 'brands_hatch', 'car' => 0]))
            ->assertOk()->assertSee('Porsche Pilot')->assertDontSee('1:22.312');
    }

    public function test_console_and_pc_are_separate_boards(): void
    {
        $this->import()->assertSuccessful();

        TimeTrialLap::create([
            'platform_identifier' => 'S765', 'platform' => 'pc', 'driver_name' => 'Pc Driver', 'track' => 'brands_hatch',
            'car_id' => 35, 'car_class' => 'GT3', 'lap_time_ms' => 81000, 'source' => 'server',
            'is_personal_best' => true, 'source_key' => sha1('pc-lap'),
        ]);

        $this->get(route('time-trials.show', 'brands_hatch'))->assertOk()->assertDontSee('Pc Driver');
        $this->get(route('time-trials.show', ['track' => 'brands_hatch', 'platform' => 'pc']))
            ->assertOk()->assertSee('Pc Driver')->assertDontSee('Alpha');
    }

    public function test_track_index_lists_tracks_with_their_record(): void
    {
        $this->import()->assertSuccessful();

        $this->get(route('time-trials.index'))
            ->assertOk()
            ->assertSee('Brands Hatch')
            ->assertSee('Nordschleife')
            ->assertSee('1:22.312');
    }

    public function test_unknown_track_is_not_found(): void
    {
        $this->get('/time-trials/not_a_track')->assertNotFound();
    }
}
