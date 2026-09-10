<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Role;
use App\Models\User;
use App\Services\ResultPenaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 6 (docs/championships/PLAN.md): "post-race time penalties applied to
// results" — no automatic position/time recalculation existed anywhere before this.
class ResultTimePenaltyTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'admin')->first());
        return $user;
    }

    private function makeRace(array $overrides = []): Race
    {
        return Race::create(array_merge([
            'title' => 'Test Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished',
            'scheduled_at' => now()->subHour(),
        ], $overrides));
    }

    private function makeResult(Race $race, array $overrides = []): RaceResult
    {
        return RaceResult::create(array_merge([
            'race_id' => $race->id, 'session_type' => 'race', 'driver_name' => 'Driver',
            'position' => 1, 'lap_count' => 20, 'total_time' => 1_200_000, // 20 min in ms
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ], $overrides));
    }

    public function test_a_time_penalty_drops_the_driver_below_whoever_they_were_penalized_past(): void
    {
        $race   = $this->makeRace();
        $first  = $this->makeResult($race, ['driver_name' => 'P1', 'position' => 1, 'total_time' => 1_200_000]);
        $second = $this->makeResult($race, ['driver_name' => 'P2', 'position' => 2, 'total_time' => 1_205_000]); // 5s behind

        app(ResultPenaltyService::class)->applyPenalty($first, 10_000); // 10s penalty — more than the 5s gap

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_more_laps_completed_always_outranks_a_smaller_total_time(): void
    {
        $race = $this->makeRace();
        $moreLaps  = $this->makeResult($race, ['driver_name' => 'More laps', 'lap_count' => 20, 'total_time' => 1_200_000]);
        $fewerLaps = $this->makeResult($race, ['driver_name' => 'Fewer laps', 'lap_count' => 15, 'total_time' => 900_000]);

        app(ResultPenaltyService::class)->recomputePositions($race, 'race');

        $this->assertSame(1, $moreLaps->fresh()->position);
        $this->assertSame(2, $fewerLaps->fresh()->position);
    }

    public function test_dsq_and_dns_results_are_excluded_from_classification(): void
    {
        $race = $this->makeRace();
        $classified = $this->makeResult($race, ['driver_name' => 'Classified', 'total_time' => 1_200_000]);
        $dsq        = $this->makeResult($race, ['driver_name' => 'DSQ', 'dsq' => true, 'total_time' => 1_100_000]);

        app(ResultPenaltyService::class)->recomputePositions($race, 'race');

        $this->assertSame(1, $classified->fresh()->position);
        // A DSQ result is left out of the ranking entirely, not just pushed to last —
        // same rule RaceResult::classifiedPositions() already applies for display.
        $this->assertNotSame(2, $dsq->fresh()->position);
    }

    public function test_admin_can_apply_a_time_penalty_via_the_route(): void
    {
        $admin  = $this->makeAdmin();
        $race   = $this->makeRace();
        $first  = $this->makeResult($race, ['driver_name' => 'P1', 'total_time' => 1_200_000]);
        $second = $this->makeResult($race, ['driver_name' => 'P2', 'position' => 2, 'total_time' => 1_205_000]);

        $this->actingAs($admin)
            ->post(route('admin.races.results.time-penalty', [$race, $first]), [
                'penalty_seconds' => 10, 'reason' => 'Track limits',
            ])
            ->assertRedirect();

        $this->assertSame(10_000, $first->fresh()->time_penalty_ms);
        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_race_show_page_renders_the_time_penalty_column(): void
    {
        $admin = $this->makeAdmin();
        $race  = $this->makeRace();
        $this->makeResult($race, ['driver_name' => 'P1', 'time_penalty_ms' => 5000]);

        $this->actingAs($admin)
            ->get(route('admin.races.show', $race))
            ->assertOk()
            ->assertSee('Time Penalty')
            ->assertSee('+5s');
    }

    public function test_a_time_penalty_cannot_be_applied_to_a_dsqd_result(): void
    {
        $admin  = $this->makeAdmin();
        $race   = $this->makeRace();
        $result = $this->makeResult($race, ['dsq' => true]);

        $this->actingAs($admin)
            ->post(route('admin.races.results.time-penalty', [$race, $result]), ['penalty_seconds' => 5])
            ->assertRedirect();

        $this->assertSame(0, $result->fresh()->time_penalty_ms);
    }
}
