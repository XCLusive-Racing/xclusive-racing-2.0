<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\AccResultImportService;
use App\Services\AccServerConfigService;
use App\Services\PracticeServer\PracticeServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// ACC PC ('ac') servers only know drivers by Steam ID. A driver registered with a console
// platform (Xbox/PSN) races PC events via the Steam account linked on their profile, so
// both the entrylist and the result import have to use that instead of platform_id.
class AccPcPlayerIdTest extends TestCase
{
    use RefreshDatabase;

    private function makeRace(string $game): Race
    {
        return Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => $game,
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
    }

    private function register(Race $race, User $user): void
    {
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
    }

    private function xboxUserWithSteam(): User
    {
        $user = User::factory()->create(['platform' => 'xbox', 'platform_id' => 'XUID-1']);
        $user->connectedAccounts()->create([
            'provider' => 'steam', 'provider_id' => 'S76561198000000001', 'username' => 'SteamName', 'connected_at' => now(),
        ]);

        return $user;
    }

    private function entryListPlayerIds(array $entryList): array
    {
        return collect($entryList['entries'])
            ->flatMap(fn ($e) => collect($e['drivers'])->pluck('playerID'))
            ->all();
    }

    public function test_pc_entrylist_uses_steam_ids(): void
    {
        $race = $this->makeRace('ac');

        $steamUser = User::factory()->create(['platform' => 'steam', 'platform_id' => 'S76561198000000002']);
        $this->register($race, $steamUser);
        $this->register($race, $this->xboxUserWithSteam());

        $playerIds = $this->entryListPlayerIds(app(AccServerConfigService::class)->entryList($race));

        $this->assertEqualsCanonicalizing(['S76561198000000002', 'S76561198000000001'], $playerIds);
    }

    public function test_console_entrylist_keeps_using_the_console_platform_id(): void
    {
        $race = $this->makeRace('acc');
        $this->register($race, $this->xboxUserWithSteam());

        $playerIds = $this->entryListPlayerIds(app(AccServerConfigService::class)->entryList($race));

        $this->assertSame(['XUID-1'], $playerIds);
    }

    public function test_pc_practice_entrylist_skips_console_drivers_without_linked_steam(): void
    {
        $race = $this->makeRace('ac');
        $this->register($race, $this->xboxUserWithSteam());
        $this->register($race, User::factory()->create(['platform' => 'ps5', 'platform_id' => 'PSN-1']));

        $result = app(PracticeServerConfigService::class)->entryList($race);

        $this->assertSame(['S76561198000000001'], $this->entryListPlayerIds($result->config));
        $this->assertSame(1, $result->skippedCount);
    }

    public function test_pc_result_import_links_a_steam_id_to_the_console_registered_driver(): void
    {
        $race = $this->makeRace('ac');
        $user = $this->xboxUserWithSteam();
        $this->register($race, $user);

        $content = json_encode([
            'sessionType' => 'Q',
            'sessionResult' => ['bestlap' => 100000, 'leaderBoardLines' => [[
                'car' => ['raceNumber' => 7, 'carModel' => 32, 'drivers' => [
                    ['playerId' => 'S76561198000000001', 'lastName' => 'Driver'],
                ]],
                'timing' => ['bestLap' => 100000, 'lapCount' => 5, 'totalTime' => 500000],
            ]]],
        ]);

        app(AccResultImportService::class)->processSessions($content, $race, 'test.json');

        $result = RaceResult::where('race_id', $race->id)->sole();
        $this->assertSame('S76561198000000001', $result->player_id);
        $this->assertSame($user->id, $result->user_id);
    }

    public function test_pc_registration_requires_a_steam_id(): void
    {
        $race = $this->makeRace('ac');
        $psnUser = User::factory()->create(['platform' => 'ps5', 'platform_id' => 'PSN-1']);

        $this->actingAs($psnUser)->post(route('events.register', $race))
            ->assertSessionHas('error', User::STEAM_REQUIRED_MESSAGE);
        $this->assertFalse(RaceRegistration::where('user_id', $psnUser->id)->exists());

        $linkedUser = $this->xboxUserWithSteam();
        $this->actingAs($linkedUser)->post(route('events.register', $race))
            ->assertSessionHas('success');
        $this->assertTrue(RaceRegistration::where('user_id', $linkedUser->id)->exists());
    }

    public function test_console_registration_does_not_need_steam(): void
    {
        $race = $this->makeRace('acc');
        $psnUser = User::factory()->create(['platform' => 'ps5', 'platform_id' => 'PSN-1']);

        $this->actingAs($psnUser)->post(route('events.register', $race))
            ->assertSessionHas('success');
    }

    public function test_primary_platform_id_wins_over_a_linked_account(): void
    {
        $owner = User::factory()->create(['platform' => 'steam', 'platform_id' => 'S76561198000000003']);
        $other = User::factory()->create(['platform' => 'xbox', 'platform_id' => 'XUID-2']);
        $other->connectedAccounts()->create([
            'provider' => 'steam', 'provider_id' => 'S76561198000000003', 'username' => 'SteamName', 'connected_at' => now(),
        ]);

        $users = User::keyedByPlayerIds(['S76561198000000003']);

        $this->assertSame($owner->id, $users->get('S76561198000000003')->id);
    }
}
