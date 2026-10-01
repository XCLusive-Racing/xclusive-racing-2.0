<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceClass;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback 2026-09: the events page's top-left tags showed only one class
// for a multiclass race (none for a multiclass championship round), and there was
// no driver swap tag.
class EventCardBadgesTest extends TestCase
{
    use RefreshDatabase;

    private function race(array $attributes = []): Race
    {
        return Race::create($attributes + [
            'title' => 'Event', 'track' => 'Monza', 'game' => 'acc', 'status' => 'open',
            'scheduled_at' => now()->addWeek(),
        ]);
    }

    public function test_a_multiclass_race_shows_every_class(): void
    {
        $race = $this->race(['title' => 'Mixed', 'car_class' => 'GT3', 'is_multiclass' => true]);
        RaceClass::create(['race_id' => $race->id, 'name' => 'GT3', 'car_class' => 'GT3', 'sort_order' => 0]);
        RaceClass::create(['race_id' => $race->id, 'name' => 'GT4', 'car_class' => 'GT4', 'sort_order' => 1]);

        $this->assertSame(['GT3', 'GT4'], $race->fresh()->displayCarClasses());
        $this->assertFalse($race->isDriverSwap());
    }

    public function test_a_multiclass_driver_swap_championship_round_shows_its_classes_and_driver_swap(): void
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $settings = ChampionshipSettingsSchema::defaults();
        $settings['format']['driver_swaps_enabled'] = true;
        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Team Sprint Series', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'visibility' => 'public', 'is_multiclass' => true, 'settings' => $settings,
        ]);
        foreach (['GT2', 'GT3', 'GT4'] as $i => $class) {
            ChampionshipClass::create(['championship_id' => $championship->id, 'name' => $class, 'car_class' => $class, 'sort_order' => $i]);
        }
        $round = $this->race(['title' => 'Team Sprint Series — Round 1', 'championship_id' => $championship->id, 'round_number' => 1, 'round_type' => 'Sprint']);

        $this->assertSame(['GT2', 'GT3', 'GT4'], $round->fresh()->displayCarClasses());
        $this->assertTrue($round->fresh()->isDriverSwap());

        // The card's top-left badges (the class filter above the cards also lists classes).
        $this->get(route('events.platform', 'acc-console'))
            ->assertOk()
            ->assertSeeInOrder(['xcl-ec2__class-badge', '>GT2</div>', '>GT3</div>', '>GT4</div>', 'DRIVER SWAP'], false)
            ->assertSee('SPRINT')
            ->assertSee('data-class="GT2,GT3,GT4"', false) // the class filter finds it under each
            ->assertDontSee('SR5 GRID');
    }

    public function test_an_endurance_race_is_a_driver_swap_not_multiclass(): void
    {
        $race = $this->race(['title' => 'Endurance Cup', 'car_class' => 'GT3', 'is_endurance' => true]);

        $this->assertSame(['GT3'], $race->fresh()->displayCarClasses());
        $this->assertTrue($race->isDriverSwap());

        $this->get(route('events.platform', 'acc-console'))->assertOk()->assertSee('DRIVER SWAP')->assertDontSee('MULTICLASS');
    }
}
