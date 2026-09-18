<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\User;
use App\Services\AccResultImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// User-directed 2026-09: "in the past we added the function to see extra stats and
// stuff on the results. But seems to be gone again."
//
// The detailed stats tabs (Best Laps / Sectors / Consistency / Lap by Lap /
// Penalties, added in 6acafb4) only render when a race has results_json_path set.
// Saving it was a private method on RaceResultController, so only the two *manual*
// import paths ever called it -- ImportGportalResults, the scheduled every-minute
// importer that brings in practically every real race, calls processSessions()
// directly and never did. Every auto-imported race therefore got no stats panel.
// Moved into processSessions() itself so every import path persists it.
class ResultsJsonPersistedTest extends TestCase
{
    use RefreshDatabase;

    private function sessionJson(string $platformId): string
    {
        return json_encode([
            'sessionType' => 'R',
            'sessionResult' => [
                'bestlap' => 95000,
                'leaderBoardLines' => [[
                    'car' => [
                        'raceNumber' => 7,
                        'carModel' => 25,
                        'drivers' => [['playerId' => $platformId, 'firstName' => 'Test', 'lastName' => 'Driver']],
                    ],
                    'timing' => ['bestLap' => 95000, 'lapCount' => 10, 'totalTime' => 960000],
                ]],
            ],
        ]);
    }

    public function test_processing_a_race_session_persists_the_raw_json_for_the_stats_panel(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['platform_id' => 'S76561198000000001']);
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'closed', 'scheduled_at' => now()->subHour(),
        ]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);

        $content = $this->sessionJson('S76561198000000001');

        [$counts] = (new AccResultImportService)->processSessions($content, $race, 'results.json');

        $this->assertSame(1, $counts['race']);

        $race->refresh();
        $this->assertSame('race-results/'.$race->id.'.json', $race->results_json_path);
        Storage::disk('local')->assertExists('race-results/'.$race->id.'.json');
        $this->assertSame($content, Storage::disk('local')->get('race-results/'.$race->id.'.json'));
    }

    public function test_a_qualifying_only_session_does_not_write_a_results_json(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['platform_id' => 'S76561198000000002']);
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'closed', 'scheduled_at' => now()->subHour(),
        ]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);

        $quali = json_decode($this->sessionJson('S76561198000000002'), true);
        $quali['sessionType'] = 'Q';

        (new AccResultImportService)->processSessions(json_encode($quali), $race, 'results.json');

        $this->assertNull($race->fresh()->results_json_path);
    }
}
