<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Services\PracticeServer\PracticeServerConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09-18: a waitlisted driver was still being pushed onto the real
// race server's entrylist, meaning they could actually take a seat and race despite
// being over capacity. They should only ever appear on the practice server (so they
// can still prep in the meantime) until a spot actually opens up for them.
class RaceEntryListWaitlistTest extends TestCase
{
    use RefreshDatabase;

    private function makeRace(array $overrides = []): Race
    {
        return Race::create(array_merge([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ], $overrides));
    }

    public function test_waitlisted_driver_is_excluded_from_the_real_race_server_entrylist(): void
    {
        $race = $this->makeRace(['max_drivers' => 1]);

        $active = User::factory()->create(['platform_id' => 'PLAYER-ACTIVE']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $active->id]);

        $waitlisted = User::factory()->create(['platform_id' => 'PLAYER-WAITLISTED']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $waitlisted->id]);

        $this->assertTrue($race->isRegistrationWaitlisted(
            RaceRegistration::where('user_id', $waitlisted->id)->first()
        ));

        $entryList = app(AccServerConfigService::class)->entryList($race);
        $playerIds = collect($entryList['entries'])
            ->flatMap(fn ($e) => collect($e['drivers'])->pluck('playerID'))
            ->all();

        $this->assertContains('PLAYER-ACTIVE', $playerIds);
        $this->assertNotContains('PLAYER-WAITLISTED', $playerIds);
    }

    public function test_waitlisted_driver_still_appears_on_the_practice_server_entrylist(): void
    {
        $race = $this->makeRace(['max_drivers' => 1]);

        $active = User::factory()->create(['platform_id' => 'PLAYER-ACTIVE']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $active->id]);

        $waitlisted = User::factory()->create(['platform_id' => 'PLAYER-WAITLISTED']);
        RaceRegistration::create(['race_id' => $race->id, 'user_id' => $waitlisted->id]);

        $result = app(PracticeServerConfigService::class)->entryList($race);
        $playerIds = collect($result->config['entries'])
            ->flatMap(fn ($e) => collect($e['drivers'])->pluck('playerID'))
            ->all();

        $this->assertContains('PLAYER-ACTIVE', $playerIds);
        $this->assertContains('PLAYER-WAITLISTED', $playerIds, 'A waitlisted driver should still be able to practice.');
    }

    // Guards Race::isRegistrationWaitlisted() against ever mis-flagging a team driver:
    // a team registration's raw rank counts every driver on every car (several rows
    // per car), which has nothing to do with the team-count cap registerTeam() enforces
    // -- team/endurance races have no waiting list at all.
    public function test_team_registrations_are_never_treated_as_waitlisted(): void
    {
        $race = $this->makeRace(['max_drivers' => 1, 'is_endurance' => true]);

        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $entry = RaceTeamEntry::create([
            'race_id' => $race->id, 'racing_team_id' => $team->id,
            'car_number' => 1, 'starting_driver_id' => $owner->id,
        ]);

        $driverA = User::factory()->create();
        $driverB = User::factory()->create();
        $driverC = User::factory()->create();
        // 3 driver rows on one car, already past the race's max_drivers of 1 by raw
        // registration count -- none of them should ever be flagged as waitlisted.
        $regA = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driverA->id, 'team_entry_id' => $entry->id]);
        $regB = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driverB->id, 'team_entry_id' => $entry->id]);
        $regC = RaceRegistration::create(['race_id' => $race->id, 'user_id' => $driverC->id, 'team_entry_id' => $entry->id]);

        $this->assertFalse($race->isRegistrationWaitlisted($regA));
        $this->assertFalse($race->isRegistrationWaitlisted($regB));
        $this->assertFalse($race->isRegistrationWaitlisted($regC));
    }
}
