<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use App\Models\League;
use App\Models\Race;
use App\Models\Report;
use App\Models\Role;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 6 (docs/championships/PLAN.md): a league championship can choose whether a
// steward-issued penalty affects points, rating, both or neither
// (settings.penalties.affects) — but rating changes stay gated behind
// xcl_rating_enabled regardless of that setting. XCL's own native championships,
// and reports with no championship at all, must keep behaving exactly as before
// this phase (unconditional rating/SR mutation, no ChampionshipPenalty).
class ReportProcessingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());
        return $user;
    }

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    private function makeReport(?Race $race, User $reportedUser, array $overrides = []): Report
    {
        return Report::create(array_merge([
            'user_id' => User::factory()->create()->id, // reporter
            'race_id' => $race?->id,
            'reported_user_id' => $reportedUser->id,
            'reported_driver_name' => $reportedUser->name,
            'description' => 'Contact at turn 1.',
            'session_type' => 'R',
            'final_penalty' => 'CT',
            'xcl_rating_deduction' => 6.75,
            'sr_deduction' => 1.20,
            'xcl_rating_return' => 2.5,
            'ready_to_process' => true,
        ], $overrides));
    }

    public function test_a_report_on_a_race_with_no_championship_still_mutates_rating_unconditionally(): void
    {
        $admin        = $this->makeAdmin();
        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500, 'sr_acc' => 5.00]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $reportedUser->refresh();
        $this->assertSame(1493, $reportedUser->elo_acc); // 1500 - round(6.75)
        $this->assertSame('3.80', number_format($reportedUser->sr_acc, 2)); // 5.00 - 1.20
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertDatabaseCount('championship_penalties', 0);
    }

    public function test_xcls_native_championship_also_mutates_rating_unconditionally(): void
    {
        $admin        = $this->makeAdmin();
        $xcl          = League::system();
        $championship = $this->makeChampionship($xcl);
        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1493, $reportedUser->fresh()->elo_acc);
        $this->assertDatabaseCount('championship_penalties', 0);
    }

    public function test_league_championship_with_affects_none_changes_neither_rating_nor_points(): void
    {
        $admin        = $this->makeAdmin();
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league); // affects defaults to 'none'
        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1500, $reportedUser->fresh()->elo_acc);
        $this->assertDatabaseCount('championship_penalties', 0);
        $this->assertSame('resolved', $report->fresh()->status);
    }

    public function test_league_championship_with_affects_rating_but_not_xcl_approved_does_not_touch_rating(): void
    {
        $admin        = $this->makeAdmin();
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['penalties' => ['affects' => 'rating']]);
        $championship->save(); // xcl_rating_enabled stays false — never touched via mass assignment

        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1500, $reportedUser->fresh()->elo_acc);
    }

    public function test_league_championship_with_affects_rating_and_xcl_approved_applies_rating(): void
    {
        $admin        = $this->makeAdmin();
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['penalties' => ['affects' => 'rating']]);
        $championship->save();
        $championship->approveXclRating($admin);

        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1493, $reportedUser->fresh()->elo_acc);
        $this->assertDatabaseCount('championship_penalties', 0);
    }

    public function test_league_championship_with_affects_points_creates_a_championship_penalty_and_skips_rating(): void
    {
        $admin        = $this->makeAdmin();
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['penalties' => ['affects' => 'points']]);
        $championship->save();
        $championship->approveXclRating($admin); // approved, but 'points' mode still shouldn't touch rating

        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1500, $reportedUser->fresh()->elo_acc);
        $this->assertDatabaseHas('championship_penalties', [
            'championship_id' => $championship->id, 'user_id' => $reportedUser->id, 'points' => 7, // round(6.75)
        ]);
    }

    public function test_league_championship_with_affects_both_applies_rating_and_creates_a_penalty(): void
    {
        $admin        = $this->makeAdmin();
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), ['penalties' => ['affects' => 'both']]);
        $championship->save();
        $championship->approveXclRating($admin);

        $race         = Race::create(['title' => 'T', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now(), 'championship_id' => $championship->id]);
        $reportedUser = User::factory()->create(['elo_acc' => 1500]);
        $report       = $this->makeReport($race, $reportedUser);

        $this->actingAs($admin)->post(route('admin.reports.process', $report))->assertRedirect();

        $this->assertSame(1493, $reportedUser->fresh()->elo_acc);
        $this->assertDatabaseHas('championship_penalties', [
            'championship_id' => $championship->id, 'user_id' => $reportedUser->id, 'points' => 7,
        ]);
    }
}
