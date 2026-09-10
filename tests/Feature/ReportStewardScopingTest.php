<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request: a league may designate its own stewards, but XCL's
// global steward pool must always remain an option too — additive, not
// exclusive (User::canModerateReport()).
class ReportStewardScopingTest extends TestCase
{
    use RefreshDatabase;

    private function makeGlobalSteward(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'steward')->first());
        return $user;
    }

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeLeagueSteward(League $league): User
    {
        $user = User::factory()->create();
        LeagueUser::create(['league_id' => $league->id, 'user_id' => $user->id, 'role' => 'steward']);
        $user->syncLeagueRoleFlags();
        return $user->fresh();
    }

    private function makeChampionship(League $league): Championship
    {
        return Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
        ]);
    }

    private function makeReportOn(Race $race): Report
    {
        $reportedUser = User::factory()->create();
        return Report::create([
            'user_id' => User::factory()->create()->id,
            'race_id' => $race->id,
            'reported_user_id' => $reportedUser->id,
            'reported_driver_name' => $reportedUser->name,
            'description' => 'Contact at turn 1.',
            'session_type' => 'R',
        ]);
    }

    public function test_leagues_own_steward_can_view_and_start_investigating_a_report_on_their_leagues_race(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $race         = Race::create(['championship_id' => $championship->id, 'title' => 'R1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $report       = $this->makeReportOn($race);

        $steward = $this->makeLeagueSteward($league);

        $this->actingAs($steward)->get(route('admin.reports.show', $report))->assertOk();
        $this->actingAs($steward)->post(route('admin.reports.start-investigating', $report))->assertRedirect();

        $this->assertSame('investigating', $report->fresh()->status);
        $this->assertSame($steward->id, $report->fresh()->steward_1_id);
    }

    public function test_a_league_steward_cannot_touch_a_report_belonging_to_a_different_league(): void
    {
        $ownLeague    = $this->makeLeague('nlrl');
        $otherLeague  = $this->makeLeague('src');
        $championship = $this->makeChampionship($otherLeague);
        $race         = Race::create(['championship_id' => $championship->id, 'title' => 'R1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $report       = $this->makeReportOn($race);

        $steward = $this->makeLeagueSteward($ownLeague);

        $this->actingAs($steward)->get(route('admin.reports.show', $report))->assertForbidden();
        $this->actingAs($steward)->post(route('admin.reports.start-investigating', $report))->assertForbidden();
    }

    public function test_a_league_steward_cannot_touch_a_report_with_no_championship_at_all(): void
    {
        $league  = $this->makeLeague('nlrl');
        $race    = Race::create(['title' => 'R1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $report  = $this->makeReportOn($race);

        $steward = $this->makeLeagueSteward($league);

        $this->actingAs($steward)->get(route('admin.reports.show', $report))->assertForbidden();
    }

    public function test_xcls_global_steward_pool_still_sees_and_acts_on_every_report_regardless_of_league(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $race         = Race::create(['championship_id' => $championship->id, 'title' => 'R1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $report       = $this->makeReportOn($race);

        $globalSteward = $this->makeGlobalSteward();

        $this->actingAs($globalSteward)->get(route('admin.reports.show', $report))->assertOk();
        $this->actingAs($globalSteward)->post(route('admin.reports.start-investigating', $report))->assertRedirect();
        $this->assertSame('investigating', $report->fresh()->status);
    }

    public function test_reports_index_is_scoped_to_the_league_stewards_own_league_only(): void
    {
        $ownLeague    = $this->makeLeague('nlrl');
        $otherLeague  = $this->makeLeague('src');
        $ownChampionship   = $this->makeChampionship($ownLeague);
        $otherChampionship = $this->makeChampionship($otherLeague);
        $ownRace   = Race::create(['championship_id' => $ownChampionship->id, 'title' => 'Own Round', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $otherRace = Race::create(['championship_id' => $otherChampionship->id, 'title' => 'Other Round', 'track' => 'Spa', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $this->makeReportOn($ownRace);
        $this->makeReportOn($otherRace);

        $steward = $this->makeLeagueSteward($ownLeague);

        // ?sort= avoids the controller's default ORDER BY FIELD(...) fallback,
        // which is MySQL-only and unsupported by the sqlite connection tests run
        // against — a pre-existing gap unrelated to this scoping change.
        $response = $this->actingAs($steward)->get(route('admin.reports.index', ['sort' => 'submitted']));
        $response->assertOk();
        $response->assertSee('Own Round');
        $response->assertDontSee('Other Round');
    }

    public function test_reports_index_shows_every_report_to_a_global_steward(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $race         = Race::create(['championship_id' => $championship->id, 'title' => 'League Round', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $this->makeReportOn($race);

        $globalSteward = $this->makeGlobalSteward();

        $this->actingAs($globalSteward)
            ->get(route('admin.reports.index', ['sort' => 'submitted']))
            ->assertOk()
            ->assertSee('League Round');
    }
}
