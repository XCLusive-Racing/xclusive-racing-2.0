<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Your Stats on the profile: pole positions, and one rating card for ACC on both platforms.
class ProfilePolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_poles_are_counted_from_qualifying_and_shown_in_your_stats(): void
    {
        $user = User::factory()->create();

        foreach ([1, 1, 4] as $position) {
            $race = Race::create(['title' => 'Sprint Race', 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDays(Race::count() + 1)]);
            RaceResult::create([
                'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => $race->track, 'race_game' => 'acc',
                'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'quali', 'user_id' => $user->id,
                'player_id' => 'P'.$user->id, 'driver_name' => $user->name, 'position' => $position,
            ]);
        }

        $this->assertSame(2, $user->raceStats()['poles']);

        $response = $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertSee('Poles')->assertDontSee('ACC CONSOLE');
        $this->assertSame(2, $response->viewData('stats')['poles']);
    }
}
