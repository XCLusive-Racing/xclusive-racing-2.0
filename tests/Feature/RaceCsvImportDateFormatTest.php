<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

// User-directed 2026-09: bulk race CSV import already reads separate "date" and
// "time" columns (not one combined column, despite the error message concatenating
// them for display) -- the real problem was that Carbon::createFromFormat('Y-m-d ...')
// rejects a spreadsheet's common "2026/09/21" (slash) rendering of that same column,
// failing every single row. Fixed by normalizing '/' to '-' before parsing, which is
// safe here because the year always comes first in both accepted shapes.
class RaceCsvImportDateFormatTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        return $user;
    }

    private function uploadCsv(User $admin, string $date)
    {
        $csv = "track,date,time\r\nMonza,{$date},20:00\r\n";
        $file = UploadedFile::fake()->createWithContent('races.csv', $csv);

        return $this->actingAs($admin)->post(route('admin.races.bulk-import-csv'), ['file' => $file]);
    }

    public function test_dash_separated_date_is_accepted(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), '2026-09-21');

        $response->assertOk();
        $response->assertJsonPath('rows.0.scheduled_at', '2026-09-21T20:00');
        $response->assertJsonMissing(['errors' => ['No valid rows found in the file.']]);
    }

    public function test_slash_separated_date_is_also_accepted(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), '2026/09/21');

        $response->assertOk();
        $response->assertJsonPath('rows.0.scheduled_at', '2026-09-21T20:00');
    }

    public function test_a_genuinely_invalid_date_is_still_rejected(): void
    {
        $response = $this->uploadCsv($this->makeAdmin(), 'not-a-date');

        $response->assertStatus(422);
        $response->assertJsonFragment(['No valid rows found in the file.']);
    }
}
