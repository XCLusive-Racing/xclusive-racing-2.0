<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Bug 2026-10-04 (Driver Swap Event, race #628): co-drivers have one result row each, and
// each row got its own finishing position (P1+P2 for the winning car, P3+P4 for the next,
// ...). The rating counts a shared car as one entry (10 cars), so positions 9-20 landed
// beyond the field and the whole bottom half took the flat max loss of -125.
class TeamRaceRatingTest extends TestCase
{
    use RefreshDatabase;

    private function teamRace(int $cars): Race
    {
        $race = Race::create([
            'title' => 'Driver Swap Event', 'track' => 'Spa', 'game' => 'acc', 'status' => 'finished',
            'is_endurance' => true, 'xcl_r_multiplier' => 2.5, 'scheduled_at' => now()->subDay(),
        ]);

        for ($car = 1; $car <= $cars; $car++) {
            foreach ([1, 2] as $seat) {
                $user = User::factory()->create(['elo_acc' => 2000]);
                RaceResult::create([
                    'race_id' => $race->id, 'race_title' => $race->title, 'race_track' => 'Spa', 'race_game' => 'acc',
                    'race_scheduled_at' => $race->scheduled_at, 'session_type' => 'race', 'race_number' => 1,
                    'user_id' => $user->id, 'player_id' => "P{$car}-{$seat}", 'driver_name' => "Car {$car} driver {$seat}",
                    'car_number' => 100 + $car, 'position' => $car, 'lap_count' => 59, 'dnf' => false,
                ]);
            }
        }

        return $race;
    }

    public function test_co_drivers_share_their_cars_position_in_the_rating(): void
    {
        $race = $this->teamRace(10);

        app(RatingService::class)->processRace($race);

        $byCar = RaceResult::where('race_id', $race->id)->get()->groupBy('car_number')->sortKeys()
            ->map(fn ($rows) => $rows->pluck('elo_change')->map(fn ($c) => round((float) $c, 2))->unique()->values()->all());

        // Same rating, same car: both co-drivers get the identical change.
        $byCar->each(fn ($changes, $car) => $this->assertCount(1, $changes, "car {$car}"));

        // P1..P10: +197.5, +155.4, +113.3, +71.3, +29.2, -12.9, -55, -97.1, then -125 for the
        // last two (at x2.5 they reach the max loss). Before the fix cars 5-10 all got -125.
        $changes = $byCar->map(fn ($c) => $c[0])->values()->all();
        $this->assertSame([197.5, 155.42, 113.33, 71.25, 29.17], array_slice($changes, 0, 5));
        $this->assertGreaterThan(-125, $changes[7]);
        $sorted = $changes;
        rsort($sorted);
        $this->assertSame($sorted, $changes);
    }

    // The public results page's Ratings tab numbers co-drivers the same way: P1, P1, P2, P2...
    public function test_the_public_ratings_tab_shows_co_drivers_sharing_their_cars_position(): void
    {
        $race = $this->teamRace(10);
        app(RatingService::class)->processRace($race);

        $html = $this->get(route('results.index', ['race' => $race->id]))->assertOk()->getContent();

        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $cells = (new \DOMXPath($dom))->query('//th[normalize-space()="Change"]/ancestor::table//tbody/tr/td[1]');
        $positions = collect(iterator_to_array($cells))->map(fn ($td) => trim($td->textContent))->all();

        $this->assertSame(['1', '1', '2', '2', '3', '3', '4', '4', '5', '5', '6', '6', '7', '7', '8', '8', '9', '9', '10', '10'], $positions);
    }

    // The Ratings tab shows each driver's Safety Rating after the race next to its change.
    public function test_the_ratings_tab_shows_the_sr_after_the_race(): void
    {
        $race = $this->teamRace(10);
        app(RatingService::class)->processRace($race);

        $result = RaceResult::where('race_id', $race->id)->orderBy('id')->first();
        $this->assertNotNull($result->sr_after);
        $this->assertEquals((float) $result->user->fresh()->sr_acc, (float) $result->sr_after);

        $this->get(route('results.index', ['race' => $race->id]))->assertOk()
            ->assertSee(number_format((float) $result->sr_after, 2))
            ->assertSee('+'.number_format((float) $result->sr_change, 2));
    }
}
