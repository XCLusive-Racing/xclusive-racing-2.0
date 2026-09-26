<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// User-directed 2026-09: a CSV with lowercase track names ("spa", "imola", "watkins
// glen") created daily races with no max drivers and no track image, since both are
// looked up by the exact track name. Track names now match case/accent-insensitively.
class RaceCsvImportTrackNameTest extends TestCase
{
    use RefreshDatabase;

    private function uploadCsv(string $track)
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $csv = "track,date,time\r\n{$track},2026-09-21,20:00\r\n";
        $file = UploadedFile::fake()->createWithContent('races.csv', $csv);

        return $this->actingAs($admin)->post(route('admin.races.bulk-import-csv'), ['file' => $file, 'game' => 'acc']);
    }

    public function test_lowercase_track_names_are_matched_to_the_known_spelling(): void
    {
        $this->uploadCsv('watkins glen')
            ->assertOk()
            ->assertJsonPath('rows.0.track', 'Watkins Glen')
            ->assertJsonPath('rows.0.title', 'Watkins Glen')
            ->assertJsonPath('errors', []);
    }

    public function test_a_track_name_without_its_accent_is_matched(): void
    {
        $this->uploadCsv('nurburgring')
            ->assertOk()
            ->assertJsonPath('rows.0.track', 'Nürburgring');
    }

    public function test_an_unknown_acc_track_is_kept_with_a_warning(): void
    {
        $this->uploadCsv('Le Mans')
            ->assertOk()
            ->assertJsonPath('rows.0.track', 'Le Mans')
            ->assertJsonPath('errors.0', 'Row 2: unknown track "Le Mans" — no max drivers or track image will be set.');
    }
}
