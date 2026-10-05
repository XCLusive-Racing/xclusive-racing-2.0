<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// User-directed 2026-10-06: a finished race nobody drove (no qualifying or race results)
// isn't listed on the results page at all.
class ResultsListTest extends TestCase
{
    use RefreshDatabase;

    private function race(string $title, ?string $sessionType): Race
    {
        $race = Race::create(['title' => $title, 'track' => 'Monza', 'game' => 'acc', 'status' => 'finished', 'scheduled_at' => now()->subDay()]);
        if ($sessionType) {
            RaceResult::create([
                'race_id' => $race->id, 'race_title' => $title, 'race_track' => 'Monza', 'race_game' => 'acc',
                'race_scheduled_at' => $race->scheduled_at, 'session_type' => $sessionType, 'race_number' => 1,
                'player_id' => 'S1', 'driver_name' => 'Test Driver', 'car_number' => 7, 'position' => 1,
            ]);
        }

        return $race;
    }

    public function test_only_races_with_results_are_listed(): void
    {
        $this->race('Raced Event', 'race');
        $this->race('Quali Only Event', 'quali');
        $empty = $this->race('Empty Event', null);

        $this->get(route('results.index'))->assertOk()
            ->assertSee('Raced Event')->assertSee('Quali Only Event')->assertDontSee('Empty Event');

        // A direct link to the empty race falls back to the newest race with results.
        $this->get(route('results.index', ['race' => $empty->id]))->assertOk()->assertDontSee('Empty Event');
    }
}
