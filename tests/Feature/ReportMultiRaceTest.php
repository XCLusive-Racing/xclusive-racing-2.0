<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-13: the Reports tab didn't support multi-race weekends — a
// report on a round with R1 and R2 couldn't say which race the incident was in.
class ReportMultiRaceTest extends TestCase
{
    use RefreshDatabase;

    private User $reporter;

    private User $reported;

    private function round(array $raceNumbers): Race
    {
        $race = Race::create(['title' => 'Round 1', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDay()]);
        $this->reporter = User::factory()->create();
        $this->reported = User::factory()->create();

        foreach ([$this->reporter, $this->reported] as $i => $user) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
            foreach ($raceNumbers as $n) {
                RaceResult::create([
                    'race_id' => $race->id, 'race_title' => 'Round 1', 'race_track' => 'Monza', 'race_game' => 'acc',
                    'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => $n,
                    'user_id' => $user->id, 'player_id' => 'S'.$user->id, 'driver_name' => $user->name, 'position' => $i + 1,
                ]);
            }
        }

        return $race;
    }

    private function payload(Race $race, array $overrides = []): array
    {
        return array_merge([
            'race_id' => $race->id, 'reported_user_id' => $this->reported->id, 'session_type' => 'R',
            'description' => 'He braked very late and took me out at turn one.', 'video_url' => 'https://youtu.be/clip',
        ], $overrides);
    }

    public function test_a_multi_race_round_report_stores_which_race(): void
    {
        $race = $this->round([1, 2]);

        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($race, ['race_number' => 2]))
            ->assertSessionHasNoErrors();

        $report = Report::sole();
        $this->assertSame(2, $report->race_number);
        $this->assertSame('Race 2', $report->sessionLabel());
    }

    public function test_a_multi_race_round_race_incident_must_name_the_race(): void
    {
        $race = $this->round([1, 2]);

        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($race))
            ->assertSessionHasErrors('race_number');
        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($race, ['race_number' => 3]))
            ->assertSessionHasErrors('race_number');

        $this->assertSame(0, Report::count());
    }

    public function test_qualifying_and_single_race_rounds_need_no_race_number(): void
    {
        $multi = $this->round([1, 2]);
        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($multi, ['session_type' => 'Q', 'race_number' => 2]))
            ->assertSessionHasNoErrors();
        $this->assertNull(Report::sole()->race_number);
        $this->assertSame('Qualifying', Report::sole()->sessionLabel());

        Report::query()->delete();
        $single = $this->round([1]);
        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($single))
            ->assertSessionHasNoErrors();
        $this->assertNull(Report::sole()->race_number);
        $this->assertSame('Race', Report::sole()->sessionLabel());
    }

    public function test_the_form_lists_each_races_numbers_and_editing_keeps_the_choice(): void
    {
        $race = $this->round([1, 2]);

        $this->actingAs($this->reporter)->get(route('reports.index'))
            ->assertOk()->assertSee('data-race-numbers="[1,2]"', false);

        $this->actingAs($this->reporter)->post(route('reports.store'), $this->payload($race, ['race_number' => 1]));
        $report = Report::sole();

        $this->actingAs($this->reporter)->get(route('reports.edit', $report))->assertOk()->assertSee('Which race');
        $this->actingAs($this->reporter)->put(route('reports.update', $report), [
            'session_type' => 'R', 'race_number' => 2,
            'description' => 'Updated: he braked very late at turn one.', 'video_url' => 'https://youtu.be/clip',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $report->fresh()->race_number);
    }
}
