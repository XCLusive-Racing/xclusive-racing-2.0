<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The championship section clean-up (2026-09-26): the wizard took over the icon and
// manual points penalties from the removed legacy admin, rounds can't share a
// number, and removing a round no longer leaves an orphaned "championship" event.
class ChampionshipCleanupTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->manager = User::factory()->leagueManager()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager->refresh();
    }

    private function makeChampionship(): Championship
    {
        return Championship::create([
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'points_system' => [25, 18], 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function makeRound(Championship $championship, int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number, 'title' => 'Round '.$number,
            'track' => 'Monza', 'game' => 'acc', 'status' => $status, 'scheduled_at' => now()->addWeek(),
            'is_championship' => true, 'event_tag' => 'championship',
        ]);
    }

    public function test_the_basics_step_saves_a_championship_icon(): void
    {
        $championship = $this->makeChampionship();

        $this->actingAs($this->manager)->put(route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'basics']), [
            'name' => 'Test Cup', 'slug' => 'test-cup', 'game' => 'acc', 'platform' => 'console', 'visibility' => 'public',
            'icon_path' => 'images/icons/cup.png',
            'settings' => ['schedule' => ['recurrence' => 'weekly', 'time_of_day' => '14:00']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('images/icons/cup.png', $championship->fresh()->icon);
    }

    public function test_a_manual_points_penalty_is_deducted_from_the_standings(): void
    {
        $championship = $this->makeChampionship();
        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id]);
        $round = $this->makeRound($championship, 1, 'finished');
        RaceResult::create([
            'race_id' => $round->id, 'session_type' => 'race', 'user_id' => $driver->id, 'driver_name' => 'D',
            'position' => 1, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);

        $this->actingAs($this->manager)->post(route('admin.leagues.championships.penalties.store', [$this->league, $championship]), [
            'user_id' => $driver->id, 'points' => 5, 'race_id' => $round->id, 'reason' => 'Track limits',
        ])->assertSessionHas('success');

        $this->assertEquals(20, $championship->refresh()->computeStandings()[0]['total_points']);

        $penalty = ChampionshipPenalty::firstOrFail();
        $this->actingAs($this->manager)
            ->delete(route('admin.leagues.championships.penalties.destroy', [$this->league, $championship, $penalty]))
            ->assertSessionHas('success');

        $this->assertEquals(25, $championship->refresh()->computeStandings()[0]['total_points']);
    }

    public function test_a_penalty_round_must_belong_to_the_championship(): void
    {
        $championship = $this->makeChampionship();
        $otherRound = $this->makeRound($this->makeChampionship(), 1);

        $this->actingAs($this->manager)->post(route('admin.leagues.championships.penalties.store', [$this->league, $championship]), [
            'user_id' => User::factory()->create()->id, 'points' => 5, 'race_id' => $otherRound->id,
        ])->assertSessionHasErrors('race_id');
    }

    public function test_two_rounds_cannot_share_a_number(): void
    {
        $championship = $this->makeChampionship();
        $this->makeRound($championship, 1);

        $this->actingAs($this->manager)->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
            'track' => 'Spa', 'round_number' => 1, 'scheduled_at' => now()->addWeeks(2)->startOfHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertSame(1, $championship->rounds()->count());
    }

    public function test_editing_a_round_keeps_its_own_number(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1);

        $this->actingAs($this->manager)->put(route('admin.leagues.championships.rounds.update', [$this->league, $championship, $round]), [
            'track' => 'Spa', 'round_number' => 1, 'scheduled_at' => now()->addWeeks(2)->startOfHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Spa', $round->fresh()->track);
    }

    public function test_removing_a_round_without_results_deletes_it(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1);

        $this->actingAs($this->manager)
            ->delete(route('admin.leagues.championships.rounds.destroy', [$this->league, $championship, $round]))
            ->assertRedirect();

        $this->assertModelMissing($round);
    }

    public function test_removing_a_round_with_results_keeps_it_as_a_standalone_race(): void
    {
        $championship = $this->makeChampionship();
        $round = $this->makeRound($championship, 1, 'finished');
        RaceResult::create([
            'race_id' => $round->id, 'session_type' => 'race', 'driver_name' => 'D',
            'position' => 1, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('admin.leagues.championships.rounds.destroy', [$this->league, $championship, $round]))
            ->assertRedirect();

        $round->refresh();
        $this->assertNull($round->championship_id);
        $this->assertFalse((bool) $round->is_championship);
        $this->assertSame('daily', $round->event_tag);
    }

    // Staff-only, like the legacy routes it replaces.
    public function test_the_old_championship_admin_redirects_to_the_wizard(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($admin)->get('/admin/championships')
            ->assertRedirect('/admin/leagues/championships');
    }
}
