<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed: "MY TEAM" needed a way for the owner to assign managers, who should
// then also be able to sign up (and unregister) the team for events/championships --
// previously that was hard-coded to the owner alone everywhere (RaceController's
// solo-race team registration, ChampionshipController's driver-swap registration).
// A manager is a racing_team_members pivot role, not a new table -- see
// RacingTeam::canManage()/User::manageableRacingTeam().
class RacingTeamManagerTest extends TestCase
{
    use RefreshDatabase;

    private function makeTeamWithManager(): array
    {
        $owner = User::factory()->create();
        $manager = User::factory()->create();
        $plainMember = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach([$manager->id, $plainMember->id]);
        $team->members()->updateExistingPivot($manager->id, ['role' => 'manager']);

        return [$owner, $manager, $plainMember, $team];
    }

    // --- Assigning managers from the My Team page ---

    public function test_owner_can_promote_and_demote_a_member_to_manager(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach($member->id);

        $this->actingAs($owner)
            ->put(route('racing-teams.members.role', [$team, $member]), ['role' => 'manager'])
            ->assertRedirect();

        $this->assertTrue($team->fresh()->isManager($member->fresh()));

        $this->actingAs($owner)
            ->put(route('racing-teams.members.role', [$team, $member]), ['role' => 'member'])
            ->assertRedirect();

        $this->assertFalse($team->fresh()->isManager($member->fresh()));
    }

    public function test_only_the_owner_can_assign_a_manager(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $intruder = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach($member->id);

        $this->actingAs($intruder)
            ->put(route('racing-teams.members.role', [$team, $member]), ['role' => 'manager'])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->isManager($member->fresh()));
    }

    public function test_the_owner_cannot_be_assigned_a_role(): void
    {
        $owner = User::factory()->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->put(route('racing-teams.members.role', [$team, $owner]), ['role' => 'manager'])
            ->assertStatus(422);
    }

    // --- Managers registering/unregistering the team for a single race ---

    public function test_a_manager_can_register_the_team_for_a_race(): void
    {
        [$owner, $manager] = $this->makeTeamWithManager();
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(), 'is_endurance' => true,
        ]);

        $this->actingAs($manager)->post(route('events.register-team', $race), [
            'car_number' => 7, 'driver_ids' => [$manager->id], 'starting_driver_id' => $manager->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('race_team_entries', ['race_id' => $race->id, 'car_number' => 7]);
    }

    public function test_a_plain_member_cannot_register_the_team_for_a_race(): void
    {
        [, , $plainMember] = $this->makeTeamWithManager();
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(), 'is_endurance' => true,
        ]);

        $this->actingAs($plainMember)->post(route('events.register-team', $race), [
            'car_number' => 7, 'driver_ids' => [$plainMember->id], 'starting_driver_id' => $plainMember->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('race_team_entries', ['race_id' => $race->id, 'car_number' => 7]);
    }

    public function test_a_manager_can_unregister_the_team_from_a_race(): void
    {
        [$owner, $manager, , $team] = $this->makeTeamWithManager();
        $race = Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(), 'is_endurance' => true,
        ]);
        $entry = RaceTeamEntry::create([
            'race_id' => $race->id, 'racing_team_id' => $team->id,
            'car_number' => 7, 'starting_driver_id' => $owner->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('events.unregister-team', [$race, $entry]))
            ->assertRedirect();

        $this->assertSoftDeleted('race_team_entries', ['id' => $entry->id]);
    }

    // --- Managers registering/unregistering the team for a driver-swaps championship ---

    private function makeDriverSwapChampionship(): Championship
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);

        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'format' => ['driver_swaps_enabled' => true],
        ]);
        $championship->save();

        return $championship;
    }

    public function test_a_manager_can_register_the_team_for_a_driver_swaps_championship(): void
    {
        [$owner, $manager, , $team] = $this->makeTeamWithManager();
        $championship = $this->makeDriverSwapChampionship();

        $this->actingAs($manager)
            ->post(route('championships.register', $championship), ['racing_team_id' => $team->id])
            ->assertRedirect();

        $this->assertDatabaseHas('championship_registrations', [
            'championship_id' => $championship->id, 'user_id' => $manager->id, 'racing_team_id' => $team->id,
        ]);
    }

    public function test_a_plain_member_cannot_register_the_team_for_a_driver_swaps_championship(): void
    {
        [, , $plainMember, $team] = $this->makeTeamWithManager();
        $championship = $this->makeDriverSwapChampionship();

        $this->actingAs($plainMember)
            ->post(route('championships.register', $championship), ['racing_team_id' => $team->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id, 'racing_team_id' => $team->id]);
    }

    public function test_a_manager_can_unregister_the_team_from_a_championship_the_owner_registered(): void
    {
        [$owner, $manager, , $team] = $this->makeTeamWithManager();
        $championship = $this->makeDriverSwapChampionship();

        $this->actingAs($owner)
            ->post(route('championships.register', $championship), ['racing_team_id' => $team->id]);

        $this->assertDatabaseHas('championship_registrations', ['championship_id' => $championship->id, 'racing_team_id' => $team->id]);

        $this->actingAs($manager)
            ->delete(route('championships.unregister', $championship))
            ->assertRedirect();

        $this->assertDatabaseMissing('championship_registrations', ['championship_id' => $championship->id, 'racing_team_id' => $team->id]);
    }
}
