<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-09 (Dutch): the dashboard's WEEKLY LEADERBOARD sidebar column
// always showed the same names in the same order, permanently -- it was ranking by
// each user's *total* elo_acc/elo_lmu/elo_iracing (a lifetime snapshot), not by
// rating actually gained *that period*, so it never reset and never reflected who was
// actually on form recently. Rebuilt off race_results.elo_change (the real signed
// per-race delta RatingService writes) summed within the current period, per game --
// matching the sidebar's own existing game filter. A later follow-up added the
// WEEKLY/MONTHLY toggle (window.__xclLeaderboards.weekly/.monthly) and an ALL TIME
// link out to the real, total-rating leaderboard page (route('drivers.index')).
class WeeklyLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeRace(string $game, Carbon $scheduledAt): Race
    {
        return Race::create([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => $game,
            'status' => 'finished', 'scheduled_at' => $scheduledAt,
        ]);
    }

    private function makeResult(Race $race, User $user, float $eloChange): RaceResult
    {
        return RaceResult::create([
            'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => $race->track,
            'race_game' => $race->game, 'race_scheduled_at' => $race->scheduled_at,
            'session_type' => 'race', 'user_id' => $user->id,
            'driver_name' => $user->name, 'position' => 1,
            'rating_before' => 1500, 'rating_after' => 1500 + $eloChange, 'elo_change' => $eloChange,
        ]);
    }

    private function fetchLeaderboards(): array
    {
        $response = $this->get('/');
        $response->assertOk();

        preg_match('/window\.__xclLeaderboards\s*=\s*(\{.*\});\s*<\/script>/s', $response->getContent(), $m);
        $this->assertNotEmpty($m, 'Could not find window.__xclLeaderboards in the response');

        return json_decode($m[1], true);
    }

    public function test_weekly_board_ranks_by_rating_gained_this_week_not_total_rating(): void
    {
        $thisWeek = now('Europe/London')->startOfWeek()->addDay();
        $lastWeek = now('Europe/London')->startOfWeek()->subWeek()->addDay();

        // Top of the OLD (total-elo) leaderboard, but gained nothing this week.
        $veteran = User::factory()->create(['name' => 'Veteran', 'elo_acc' => 3000]);
        $race0 = $this->makeRace('acc', $lastWeek);
        $this->makeResult($race0, $veteran, 5);

        // Lower total rating, but the real top gainer this week (two races, summed).
        $riser = User::factory()->create(['name' => 'Riser', 'elo_acc' => 1600]);
        $raceA = $this->makeRace('acc', $thisWeek);
        $raceB = $this->makeRace('acc', $thisWeek->copy()->addDay());
        $this->makeResult($raceA, $riser, 40);
        $this->makeResult($raceB, $riser, 25);

        // Net negative this week -- must not appear as a "gain".
        $faller = User::factory()->create(['name' => 'Faller', 'elo_acc' => 1500]);
        $raceC = $this->makeRace('acc', $thisWeek);
        $this->makeResult($raceC, $faller, -30);

        $acc = collect($this->fetchLeaderboards()['weekly']['acc']);

        $this->assertSame('Riser', $acc->first()['name']);
        $this->assertSame(65, $acc->first()['gain']);
        $this->assertFalse($acc->contains('name', 'Veteran'), 'Veteran gained nothing this week and should not appear');
        $this->assertFalse($acc->contains('name', 'Faller'), 'Faller had a net-negative week and should not appear as a gain');
    }

    public function test_monthly_board_includes_a_result_outside_the_current_week_but_inside_the_month(): void
    {
        $earlierThisMonth = now('Europe/London')->startOfMonth()->addDays(2);
        $thisWeekStart = now('Europe/London')->startOfWeek();
        $thisWeekEnd = now('Europe/London')->endOfWeek();
        // Guard the fixture against a race scheduled in this same week — this test is
        // specifically about "outside the week, inside the month".
        if ($earlierThisMonth->betweenIncluded($thisWeekStart, $thisWeekEnd)) {
            $this->markTestSkipped('Start of month falls in the current week this run — not exercising the intended gap.');
        }

        $driver = User::factory()->create(['name' => 'MonthlyOnly']);
        $race = $this->makeRace('acc', $earlierThisMonth);
        $this->makeResult($race, $driver, 50);

        $boards = $this->fetchLeaderboards();

        $this->assertFalse(collect($boards['weekly']['acc'])->contains('name', 'MonthlyOnly'), 'Outside the current week, should not be on the weekly board');
        $this->assertTrue(collect($boards['monthly']['acc'])->contains('name', 'MonthlyOnly'), 'Inside the current month, should be on the monthly board');
    }

    public function test_all_time_link_points_at_the_real_drivers_leaderboard(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('drivers.index'), false);
    }
}
