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

// The "Team / Quote" profile field -- shown in-game on a second line under a solo
// driver's name (AccServerConfigService::entryLastName()), capped at 16 characters.
// User-directed 2026-09-24 it was opened to everyone; 2026-10-03 it's a supporter perk
// again (part of the membership), together with the profile stream link.
class ProfileTeamQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_supporter_can_set_their_team_quote_and_stream_link(): void
    {
        $user = User::factory()->create(['is_supporter' => true, 'team' => null]);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => 'Night Owls RT', 'stream_url' => 'https://twitch.tv/nightowls'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Night Owls RT', $user->fresh()->team);
        $this->assertSame('https://twitch.tv/nightowls', $user->fresh()->stream_url);
    }

    public function test_a_non_supporter_cannot_set_team_quote_or_stream_link(): void
    {
        $user = User::factory()->create(['is_supporter' => false, 'team' => null]);

        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Become a supporter');

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'team' => 'Night Owls RT', 'stream_url' => 'https://twitch.tv/nightowls'])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->team);
        $this->assertNull($user->fresh()->stream_url);
    }

    public function test_the_profile_stream_link_must_be_twitch_or_youtube(): void
    {
        $user = User::factory()->create(['is_supporter' => true]);

        $this->actingAs($user)
            ->put(route('profile.update'), ['name' => $user->name, 'stream_url' => 'https://example.com/live'])
            ->assertSessionHasErrors('stream_url');
    }

    // A lapsed supporter keeps the stored quote, but it's no longer shown in-game.
    public function test_a_non_supporters_stored_quote_is_not_shown_in_game(): void
    {
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $user = User::factory()->create(['name' => 'Lapsed', 'team' => 'Old Quote', 'platform_id' => 'X-1', 'is_supporter' => false]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);

        $lastName = app(AccServerConfigService::class)->entryList($race)['entries'][0]['drivers'][0]['lastName'];

        $this->assertSame('Lapsed', $lastName);
    }

    public function test_team_quote_is_capped_at_16_characters(): void
    {
        $user = User::factory()->create(['team' => 'Old', 'is_supporter' => true]);

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
        $withQuote = User::factory()->create(['name' => 'DeEchteOlle', 'team' => 'XCLusive Developer', 'platform_id' => 'X-1', 'is_supporter' => true]);
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
    // driver's name instead of their personal quote, cut at 24 characters (solo quotes are
    // already capped at 16 by the profile form).
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
        $driver = User::factory()->create(['name' => 'DeEchteOlle', 'team' => 'XCLusive Developer', 'platform_id' => 'X-1', 'is_supporter' => true]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driver->id, 'team_entry_id' => $entry->id]);

        $lastName = app(AccServerConfigService::class)->entryList($race)['entries'][0]['drivers'][0]['lastName'];

        $this->assertSame("DeEchteOlle\nVery Long Endurance Team", $lastName);
    }

    // In-game the name follows the driver's site choice: the real name shortened
    // ("Jan Jansen" -> "J. Jansen", leaderboard letters from the last name), else the
    // gamertag without a Discord-style "#1234" suffix.
    public function test_entrylist_name_follows_the_real_name_choice_shortened(): void
    {
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
        $realName = User::factory()->create([
            'name' => 'FastGuy', 'team' => 'Quote', 'platform_id' => 'X-1', 'is_supporter' => true,
            'first_name' => 'jan', 'last_name' => 'Jansen', 'display_name_preference' => User::DISPLAY_REAL_NAME,
        ]);
        $gamertag = User::factory()->create([
            'name' => 'SlowGuy#1234', 'team' => null, 'platform_id' => 'X-2',
            'first_name' => 'Piet', 'last_name' => 'Pietersen', 'display_name_preference' => User::DISPLAY_GAMERTAG,
        ]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $realName->id]);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $gamertag->id]);

        $drivers = collect(app(AccServerConfigService::class)->entryList($race)['entries'])
            ->flatMap(fn ($e) => $e['drivers'])->keyBy('playerID');

        $this->assertSame('', $drivers['X-1']['firstName']);
        $this->assertSame("J. Jansen\nQuote", $drivers['X-1']['lastName']);
        $this->assertSame('JAN', $drivers['X-1']['shortName']);

        $this->assertSame('SlowGuy', $drivers['X-2']['lastName']);
        $this->assertSame('SLO', $drivers['X-2']['shortName']);
    }
}
