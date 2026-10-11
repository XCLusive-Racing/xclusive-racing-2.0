<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceResult;
use App\Models\Report;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10: Safety Rating is earned in any race with at least 2 drivers on
// track; XCL Rating keeps its minimum of 8. And a driver can only report an incident from
// a race of the last 7 days.
class SmallFieldSafetyRatingTest extends TestCase
{
    use RefreshDatabase;

    private function race(int $drivers, $scheduledAt = null): Race
    {
        $race = Race::create([
            'title' => 'Daily', 'track' => 'Spa', 'game' => 'acc', 'status' => 'finished',
            'scheduled_at' => $scheduledAt ?? now()->subDay(),
        ]);

        for ($i = 1; $i <= $drivers; $i++) {
            $user = User::factory()->create(['elo_acc' => 2000, 'sr_acc' => 4.00]);
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $user->id]);
            RaceResult::create([
                'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => 'Spa', 'race_game' => 'acc',
                'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
                'user_id' => $user->id, 'player_id' => "P{$i}", 'driver_name' => "Driver {$i}",
                'car_number' => 100 + $i, 'position' => $i, 'lap_count' => 20, 'dnf' => false,
            ]);
        }

        return $race;
    }

    public function test_a_small_field_earns_safety_rating_but_no_xcl_rating(): void
    {
        $race = $this->race(3);

        app(RatingService::class)->processRace($race);

        foreach (RaceResult::where('race_id', $race->id)->with('user')->get() as $result) {
            $this->assertNull($result->elo_change);
            $this->assertGreaterThan(0, (float) $result->sr_change);
            $this->assertSame(4.55, (float) $result->sr_after);
            $this->assertSame(4.55, (float) $result->user->sr_acc);
            $this->assertSame(2000, (int) $result->user->elo_acc);
        }

        // Recalculating doesn't stack a second gain on top.
        app(RatingService::class)->processRace($race);
        $this->assertSame(4.55, (float) RaceResult::where('race_id', $race->id)->first()->user->sr_acc);

        $this->get(route('results.index', ['race' => $race->id]))->assertOk()
            ->assertSee('Safety Rating still counts')->assertSee('+0.55');
    }

    public function test_a_driver_alone_in_the_lobby_earns_nothing(): void
    {
        $race = $this->race(1);

        app(RatingService::class)->processRace($race);

        $result = RaceResult::where('race_id', $race->id)->first();
        $this->assertNull($result->sr_change);
        $this->assertSame(4.00, (float) $result->user->sr_acc);
    }

    public function test_a_full_field_still_gets_both(): void
    {
        $race = $this->race(8);

        app(RatingService::class)->processRace($race);

        $winner = RaceResult::where('race_id', $race->id)->where('position', 1)->first();
        $this->assertGreaterThan(0, (float) $winner->elo_change);
        $this->assertGreaterThan(0, (float) $winner->sr_change);
    }

    public function test_only_races_of_the_last_week_can_be_reported(): void
    {
        $recent = $this->race(2, now()->subDays(6));
        $old = $this->race(2, now()->subDays(8));
        $me = User::factory()->create();
        foreach ([$recent, $old] as $race) {
            RaceRegistration::create(['race_id' => $race->id, 'user_id' => $me->id]);
        }

        $races = $this->actingAs($me)->get(route('reports.index'))->assertOk()->viewData('races');
        $this->assertSame([$recent->id], $races->pluck('id')->all());

        $this->actingAs($me)->post(route('reports.store'), [
            'race_id' => $old->id,
            'reported_user_id' => $old->registrations()->where('user_id', '!=', $me->id)->value('user_id'),
            'session_type' => 'R', 'description' => 'Pushed me off the track at turn one.',
            'video_url' => 'https://example.com/clip',
        ])->assertSessionHasErrors('race_id');
        $this->assertSame(0, Report::count());
    }
}
