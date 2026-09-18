<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// User-directed 2026-09: bulk race CSV import rejected min_rating/max_rating cells
// written with a human qualifier the column header already implies -- "bronze+" in
// a min_rating column, "rookie max" in a max_rating one -- even though the tier name
// itself (bronze/rookie) is perfectly valid. Fixed by stripping the qualifier before
// matching against the known tier list, so these round-trip straight into the race's
// requirements instead of being silently ignored.
class RaceCsvImportRatingTierTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function uploadCsv(User $admin, string $minRating, string $maxRating)
    {
        $csv = "track,date,time,min_rating,max_rating\r\nMonza,2026-09-21,20:00,{$minRating},{$maxRating}\r\n";
        $file = UploadedFile::fake()->createWithContent('races.csv', $csv);

        return $this->actingAs($admin)->post(route('admin.races.bulk-import-csv'), ['file' => $file]);
    }

    public function test_a_trailing_plus_on_min_rating_is_recognized(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), 'bronze+', '');

        $response->assertOk();
        $response->assertJsonPath('rows.0.min_rating', 'bronze');
        $response->assertJsonPath('errors', []);
    }

    public function test_a_max_qualifier_on_max_rating_is_recognized(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), '', 'rookie max');

        $response->assertOk();
        $response->assertJsonPath('rows.0.max_rating', 'rookie');
        $response->assertJsonPath('errors', []);
    }

    public function test_a_genuinely_unknown_tier_is_still_ignored_with_a_warning(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), 'diamond+', '');

        $response->assertOk();
        $response->assertJsonPath('rows.0.min_rating', '');
        $response->assertJsonPath('errors.0', 'Row 2: unknown min_rating "diamond" — ignored.');
    }
}
