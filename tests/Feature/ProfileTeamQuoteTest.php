<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\AccResultImportService;
use App\Services\AccServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09-24: the "Team / Quote" profile field -- shown in-game on a second
// line under a solo driver's name (AccServerConfigService::entryLastName()) -- used to be a
// supporter-only perk. It's now open to everyone, still capped at 16 characters.
class ProfileTeamQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_supporter_can_set_their_team_quote(): void
    {
        $user = User::factory()->create(['is_supporter' => false, 'team' => null]);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => 'Night Owls RT'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Night Owls RT', $user->fresh()->team);
    }

    public function test_team_quote_is_capped_at_16_characters(): void
    {
        $user = User::factory()->create(['team' => 'Old']);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => str_repeat('x', 17)])
            ->assertSessionHasErrors('team');

        $this->assertSame('Old', $user->fresh()->team);
    }

    // In-game the name tag shows the driver's name with their Team / Quote on the line
    // under it -- a newline inside lastName (teamName isn't an ACC entrylist field). The
    // result import must read just the name back, without that second line.
    public function test_team_quote_goes_under_the_name_in_game_and_is_stripped_on_import(): void
    {
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $withQuote = User::factory()->create(['name' => 'DeEchteOlle', 'team' => 'XCLusive Developer', 'platform_id' => 'X-1']);
        $noQuote = User::factory()->create(['name' => 'Plain', 'team' => null, 'platform_id' => 'X-2']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $withQuote->id]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $noQuote->id]);

        $drivers = collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->flatMap(fn ($e) => $e['drivers'])->keyBy('playerID');

        $this->assertSame("DeEchteOlle\nXCLusive Developer", $drivers['X-1']['lastName']);
        $this->assertSame('Plain', $drivers['X-2']['lastName']);

        $content = json_encode([
            'sessionType' => 'Q',
            'sessionResult' => ['bestlap' => 100000, 'leaderBoardLines' => [[
                'car' => ['raceNumber' => 7, 'carModel' => 32, 'drivers' => [
                    ['playerId' => 'X-1', 'firstName' => '', 'lastName' => "DeEchteOlle\nXCLusive Developer"],
                ]],
                'timing' => ['bestLap' => 100000, 'lapCount' => 5, 'totalTime' => 500000],
            ]]],
        ]);
        app(AccResultImportService::class)->processSessions($content, $race, 'test.json');

        $this->assertSame('DeEchteOlle', RaceResult::where('race_id', $race->id)->sole()->driver_name);
    }

    // A team entry (driver swap / endurance) shows the team the car races for under every
    // driver's name instead of their personal quote -- long team names are not shortened.
    public function test_team_entry_drivers_get_their_team_name_under_their_name(): void
    {
        $race = Race::create([
            'title' => 'Endurance', 'track' => 'Spa', 'game' => 'acc', 'is_endurance' => true,
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Very Long Endurance Team Name Racing', 'tag' => 'VLE', 'owner_id' => $owner->id]);
        $entry = RaceTeamEntry::create([
            'race_id' => $race->id, 'racing_team_id' => $team->id,
            'car_number' => 1, 'starting_driver_id' => $owner->id,
        ]);
        $driver = User::factory()->create(['name' => 'DeEchteOlle', 'team' => 'XCLusive Developer', 'platform_id' => 'X-1']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driver->id, 'team_entry_id' => $entry->id]);

        $lastName = app(AccServerConfigService::class)->entryList($race)['entries'][0]['drivers'][0]['lastName'];

        $this->assertSame("DeEchteOlle\nVery Long Endurance Team Name Racing", $lastName);
    }
}
