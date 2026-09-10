<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\FtpServer;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request: "make round creation a bit like our bulk maker" — a bulk
// round generator alongside the existing single Add Round form, sharing the
// exact same slot/validity rules via ChampionshipWizardController::resolveRoundRow().
class RoundCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeManager(League $league): User
    {
        $manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $manager->id, 'role' => 'manager']);
        $manager->syncLeagueRoleFlags();
        return $manager->fresh();
    }

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'draft', 'visibility' => 'public', 'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    public function test_round_create_page_renders_single_and_bulk_panels(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.rounds.create', [$league, $championship]))
            ->assertOk()
            ->assertSee('Single Round')
            ->assertSee('Bulk Add Rounds')
            ->assertSee('Generate Rows');
    }

    // Regression test for the addRound()/resolveRoundRow() refactor — proves
    // single-round creation still behaves exactly as before it was split out.
    public function test_single_round_creation_still_works_after_the_refactor(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.store', [$league, $championship]), [
                'track' => 'Monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('races', [
            'championship_id' => $championship->id, 'track' => 'Monza', 'round_number' => 1,
        ]);
    }

    public function test_bulk_add_rounds_creates_every_row(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]), [
                'race_duration' => 30,
                'rounds' => [
                    ['track' => 'Monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i')],
                    ['track' => 'Spa', 'scheduled_at' => now()->addWeeks(2)->startOfHour()->format('Y-m-d\TH:i')],
                    ['track' => 'Silverstone', 'scheduled_at' => now()->addWeeks(3)->startOfHour()->format('Y-m-d\TH:i')],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(3, $championship->rounds()->count());
        $this->assertDatabaseHas('races', ['championship_id' => $championship->id, 'track' => 'Monza', 'round_number' => 1]);
        $this->assertDatabaseHas('races', ['championship_id' => $championship->id, 'track' => 'Spa', 'round_number' => 2]);
        $this->assertDatabaseHas('races', ['championship_id' => $championship->id, 'track' => 'Silverstone', 'round_number' => 3]);
    }

    public function test_bulk_add_rounds_is_all_or_nothing_when_one_row_is_invalid(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]), [
                'race_duration' => 30,
                'rounds' => [
                    ['track' => 'Monza', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i')],
                    // Half past the hour — resolveRoundRow() rejects this.
                    ['track' => 'Spa', 'scheduled_at' => now()->addWeeks(2)->startOfHour()->addMinutes(30)->format('Y-m-d\TH:i')],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('rounds');

        $this->assertSame(0, $championship->rounds()->count());
    }

    public function test_bulk_add_rounds_rejects_two_rows_claiming_the_same_server_slot(): void
    {
        $league = $this->makeLeague('nlrl');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $sameSlot = now()->addWeek()->startOfHour()->format('Y-m-d\TH:i');

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]), [
                'race_duration'  => 30,
                'ftp_server_id'  => $server->id,
                'rounds' => [
                    ['track' => 'Monza', 'scheduled_at' => $sameSlot],
                    ['track' => 'Spa', 'scheduled_at' => $sameSlot],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('rounds');

        $this->assertSame(0, $championship->rounds()->count());
    }

    public function test_bulk_row_track_is_required(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);

        $this->actingAs($manager)
            ->post(route('admin.leagues.championships.rounds.bulk-store', [$league, $championship]), [
                'rounds' => [
                    ['track' => '', 'scheduled_at' => now()->addWeek()->startOfHour()->format('Y-m-d\TH:i')],
                ],
            ])
            ->assertSessionHasErrors('rounds.0.track');
    }

    // --- Edit Round (refinement request item 8: rounds only had Add/Remove) ---

    private function makeRound(Championship $championship, array $overrides = []): Race
    {
        return Race::create(array_merge([
            'championship_id' => $championship->id, 'round_number' => 1,
            'title' => 'Test Cup — Round 1', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek()->startOfHour(),
        ], $overrides));
    }

    public function test_round_edit_page_renders_with_the_rounds_current_values(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);
        $race         = $this->makeRound($championship, ['track' => 'Spa']);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.rounds.edit', [$league, $championship, $race]))
            ->assertOk()
            ->assertSee('Spa', false);
    }

    public function test_updating_a_round_changes_its_track_and_time(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);
        $race         = $this->makeRound($championship);

        $newTime = now()->addWeeks(2)->startOfHour();

        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.rounds.update', [$league, $championship, $race]), [
                'track' => 'Spa', 'scheduled_at' => $newTime->format('Y-m-d\TH:i'), 'round_number' => 1,
                'race_duration' => 45,
            ])
            ->assertRedirect();

        $race->refresh();
        $this->assertSame('Spa', $race->track);
        $this->assertSame(45, $race->race_duration);
        $this->assertNotNull($race->scheduled_at);
    }

    public function test_editing_a_round_without_changing_its_time_does_not_collide_with_itself(): void
    {
        $league = $this->makeLeague('nlrl');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);
        $slot         = now()->addWeek()->startOfHour()->format('Y-m-d\TH:i');

        // Created through the actual HTTP flow (not Race::create() directly) so
        // the round's stored slot_time is the exact UTC value resolveRoundRow()
        // itself would derive from this UK-local slot string.
        $this->actingAs($manager)->post(route('admin.leagues.championships.rounds.store', [$league, $championship]), [
            'track' => 'Monza', 'scheduled_at' => $slot, 'ftp_server_id' => $server->id, 'round_number' => 1,
        ])->assertRedirect();
        $race = Race::where('championship_id', $championship->id)->firstOrFail();

        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.rounds.update', [$league, $championship, $race]), [
                'track' => 'Spa', 'scheduled_at' => $slot, 'ftp_server_id' => $server->id,
                'race_duration' => 45,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Spa', $race->fresh()->track);
    }

    public function test_editing_a_round_into_a_slot_another_round_already_holds_is_rejected(): void
    {
        $league = $this->makeLeague('nlrl');
        $server = FtpServer::create([
            'name' => 'NLRL Server', 'host' => '1.2.3.4', 'port' => 21,
            'username' => 'u', 'password' => 'p', 'path' => '/results',
            'server_type' => 'scheduled', 'league_id' => $league->id,
        ]);
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);
        $takenSlot    = now()->addWeek()->startOfHour()->format('Y-m-d\TH:i');

        // Created through the same HTTP flow (not directly via Race::create()) so
        // both rounds' slot_time go through the exact same Europe/London->UTC
        // conversion resolveRoundRow() applies — otherwise a raw Carbon set
        // straight on the model wouldn't line up with what the edit submits.
        $this->actingAs($manager)->post(route('admin.leagues.championships.rounds.store', [$league, $championship]), [
            'track' => 'Monza', 'scheduled_at' => $takenSlot, 'ftp_server_id' => $server->id, 'round_number' => 1,
        ])->assertRedirect();
        $race2 = $this->makeRound($championship, ['round_number' => 2, 'scheduled_at' => now()->addWeeks(2)]);

        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.rounds.update', [$league, $championship, $race2]), [
                'track' => 'Spa', 'scheduled_at' => $takenSlot, 'ftp_server_id' => $server->id,
                'race_duration' => 45,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_editing_a_round_never_resets_a_finished_rounds_status(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $manager      = $this->makeManager($league);
        $race         = $this->makeRound($championship, ['status' => 'finished']);

        $this->actingAs($manager)
            ->put(route('admin.leagues.championships.rounds.update', [$league, $championship, $race]), [
                'track' => 'Spa', 'scheduled_at' => $race->scheduled_at->format('Y-m-d\TH:i'), 'race_duration' => 45,
            ])
            ->assertRedirect();

        $this->assertSame('finished', $race->fresh()->status);
    }

    public function test_a_manager_cannot_edit_a_round_belonging_to_a_different_championship(): void
    {
        $league        = $this->makeLeague('nlrl');
        $championship  = $this->makeChampionship($league);
        $otherChampionship = $this->makeChampionship($league, ['name' => 'Other Cup']);
        $manager       = $this->makeManager($league);
        $otherRace     = $this->makeRound($otherChampionship);

        $this->actingAs($manager)
            ->get(route('admin.leagues.championships.rounds.edit', [$league, $championship, $otherRace]))
            ->assertNotFound();
    }
}
