<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// User-directed 2026-09: the race CSV import gets a rain_level column (0.0-1.0, same as
// the Create Race slider), and bulkStore() now keeps each row's own rain level instead
// of only the page-wide one.
class RaceCsvImportRainLevelTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function uploadCsv(string $weather, string $rainLevel)
    {
        $csv = "track,date,time,weather,rain_level\r\nMonza,2026-09-21,20:00,{$weather},\"{$rainLevel}\"\r\n";
        $file = UploadedFile::fake()->createWithContent('races.csv', $csv);

        return $this->actingAs($this->makeAdmin())->post(route('admin.races.bulk-import-csv'), ['file' => $file]);
    }

    public function test_rain_level_is_read_for_wet_weather_with_a_comma_decimal(): void
    {
        $response = $this->uploadCsv('wet', '0,3');

        $response->assertOk();
        $response->assertJsonPath('rows.0.rain_level', '0.3');
        $response->assertJsonPath('errors', []);
    }

    public function test_rain_level_on_dry_weather_is_ignored_with_a_warning(): void
    {
        $response = $this->uploadCsv('dry', '0.2');

        $response->assertOk();
        $response->assertJsonPath('rows.0.rain_level', '');
        $response->assertJsonPath('errors.0', 'Row 2: rain_level only applies to wet or mixed weather — ignored.');
    }

    public function test_rain_level_out_of_range_is_ignored_with_a_warning(): void
    {
        $response = $this->uploadCsv('wet', '1.5');

        $response->assertOk();
        $response->assertJsonPath('rows.0.rain_level', '');
        $response->assertJsonPath('errors.0', 'Row 2: invalid rain_level "1.5" (expected 0.0-1.0) — ignored.');
    }

    public function test_bulk_store_saves_each_rows_own_rain_level(): void
    {
        $start = now()->addWeek()->startOfHour();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.races.bulk-store'), [
                'game' => 'acc',
                'race_duration' => 30,
                'events' => [
                    ['title' => 'Monza', 'track' => 'Monza', 'scheduled_at' => $start->format('Y-m-d\TH:i'), 'weather' => 'wet', 'rain_level' => '0.4', 'time_of_day' => '', 'max_drivers' => ''],
                    ['title' => 'Spa', 'track' => 'Spa', 'scheduled_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'weather' => 'dry', 'rain_level' => '0.4', 'time_of_day' => '', 'max_drivers' => ''],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(0.4, Race::where('track', 'Monza')->value('rain_level'));
        // Dry weather never carries a rain level (normalizeRainLevel()).
        $this->assertNull(Race::where('track', 'Spa')->value('rain_level'));
    }
}
