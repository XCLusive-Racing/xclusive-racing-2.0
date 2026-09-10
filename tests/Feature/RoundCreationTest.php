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
}
