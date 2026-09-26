<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// User-directed 2026-09: ACC Console multiclass events are imported with car_class left
// "open" (so the entry list stays open) and the two classes spelled out in their own
// multiclass_class_1/_2 columns, each with its own min SR and min rating.
class RaceCsvImportMulticlassTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function uploadCsv(string $csv)
    {
        $file = UploadedFile::fake()->createWithContent('races.csv', $csv);

        return $this->actingAs($this->makeAdmin())->post(route('admin.races.bulk-import-csv'), ['file' => $file]);
    }

    public function test_multiclass_columns_become_classes_with_their_own_requirements(): void
    {
        $response = $this->uploadCsv(
            "track,date,time,car_class,multiclass_class_1,multiclass_class_1_min_sr,multiclass_class_1_min_rating,multiclass_class_2,multiclass_class_2_min_sr,multiclass_class_2_min_rating\r\n"
            ."Mount Panorama,2026-09-21,20:00,open,GT3,5,bronze+,GT4,,rookie\r\n"
        );

        $response->assertOk();
        $response->assertJsonPath('errors', []);
        $response->assertJsonPath('rows.0.car_class', 'open');
        $response->assertJsonPath('rows.0.classes.0.car_class', 'GT3');
        $response->assertJsonPath('rows.0.classes.0.sr_requirement', '5');
        $response->assertJsonPath('rows.0.classes.0.min_rating', 'bronze');
        $response->assertJsonPath('rows.0.classes.1.car_class', 'GT4');
        $response->assertJsonPath('rows.0.classes.1.sr_requirement', null);
        $response->assertJsonPath('rows.0.classes.1.min_rating', 'rookie');
    }

    public function test_only_one_multiclass_class_is_imported_as_single_class_with_a_warning(): void
    {
        $response = $this->uploadCsv(
            "track,date,time,car_class,multiclass_class_1\r\n"
            ."Monza,2026-09-21,20:00,open,GT3\r\n"
        );

        $response->assertOk();
        $response->assertJsonPath('rows.0.classes', []);
        $response->assertJsonPath('errors.0', 'Row 2: multiclass needs both multiclass_class_1 and multiclass_class_2 — imported as a single-class race.');
    }

    public function test_legacy_car_class_2_column_still_imports_as_multiclass(): void
    {
        $response = $this->uploadCsv(
            "track,date,time,car_class,car_class_2\r\n"
            ."Monza,2026-09-21,20:00,GT3,GT4\r\n"
        );

        $response->assertOk();
        $response->assertJsonPath('rows.0.classes.0.min_rating', 'bronze');
        $response->assertJsonPath('rows.0.classes.1.car_class', 'GT4');
    }
}
