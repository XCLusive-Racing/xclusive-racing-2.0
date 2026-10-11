<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10: the profile page's Rating Changes tab lists every XCL Rating and
// Safety Rating gain and loss — from races, steward penalties and rating returned.
class ProfileRatingHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function race(string $title, $scheduledAt): Race
    {
        return Race::create(['title' => $title, 'track' => 'Spa', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => $scheduledAt]);
    }

    private function raceResult(Race $race, User $user, array $attributes): RaceResult
    {
        return RaceResult::create($attributes + [
            'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => 'Spa', 'race_game' => 'acc',
            'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
            'user_id' => $user->id, 'player_id' => 'P'.$user->id, 'driver_name' => $user->name,
            'position' => 1, 'dnf' => false,
        ]);
    }

    private function report(Race $race, User $reporter, User $reported, array $attributes = []): Report
    {
        return Report::create($attributes + [
            'user_id' => $reporter->id, 'race_id' => $race->id, 'reported_user_id' => $reported->id,
            'reported_driver_name' => $reported->name, 'session_type' => 'R',
            'description' => 'Contact at turn one.', 'video_url' => 'https://example.com/clip',
            'status' => 'resolved', 'final_penalty' => 'Causing a collision', 'processed_at' => now()->subDay(),
            'xcl_rating_deduction' => 12, 'xcl_rating_return' => 6, 'sr_deduction' => 0.5,
        ]);
    }

    public function test_the_profile_lists_gains_and_losses_from_races_and_penalties_newest_first(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $rated = $this->race('Rated race', now()->subDays(5));
        $this->raceResult($rated, $me, ['elo_change' => 31.4, 'sr_change' => 0.5]);
        $small = $this->race('Small race', now()->subDays(3));
        $this->raceResult($small, $me, ['sr_change' => 0.45]);
        $unrated = $this->race('Unrated race', now()->subDays(4));
        $this->raceResult($unrated, $me, []);

        $this->report($rated, $other, $me, ['processed_at' => now()->subDays(2)]);
        $this->report($small, $me, $other, ['processed_at' => now()->subDay()]);
        // Still with the stewards: no rating has moved yet.
        $this->report($small, $other, $me, ['status' => 'pending', 'processed_at' => null]);

        $history = $this->actingAs($me)->get(route('profile'))->assertOk()
            ->assertSee('Rating Changes')
            ->viewData('ratingHistory');

        $this->assertSame([
            ['return', 'Small race', 6.0, null],
            ['penalty', 'Rated race', -12.0, -0.5],
            ['race', 'Small race', null, 0.45],
            ['race', 'Rated race', 31.4, 0.5],
        ], $history->map(fn (array $e) => [$e['type'], $e['title'], $e['elo'], $e['sr']])->all());
    }

    public function test_a_profile_without_rated_races_shows_the_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile'))->assertOk()
            ->assertSee('No rating changes yet');
    }
}
