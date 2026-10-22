<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceRegistration;
use App\Models\RaceTeamEntry;
use App\Models\RacingTeam;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// A multiclass driver-swap championship's round lists its team cars in one box per
// class (from each car's championship entry), like a multiclass event's class boxes.
class ChampionshipTeamRoundClassBoxesTest extends TestCase
{
    use RefreshDatabase;

    private Championship $championship;

    private Race $round;

    protected function setUp(): void
    {
        parent::setUp();

        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $settings = array_replace_recursive(ChampionshipSettingsSchema::defaults(), [
            'format' => ['driver_swaps_enabled' => true, 'team_registration_scope' => 'championship', 'multiclass_enabled' => true],
        ]);
        $this->championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Team Sprint Series', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'registration_open' => true, 'visibility' => 'public',
            'is_multiclass' => true, 'settings' => $settings,
        ]);
        $this->round = Race::create([
            'championship_id' => $this->championship->id, 'round_number' => 1,
            'title' => 'Round 1', 'track' => 'Monza', 'game' => 'acc',
            'status' => 'open', 'scheduled_at' => now()->addWeek(),
        ]);
    }

    private function enterCar(string $team, int $number, ?ChampionshipClass $class, array $driverNames): void
    {
        $owner = User::factory()->create();
        $racingTeam = RacingTeam::create(['name' => $team, 'tag' => strtoupper(substr($team, 0, 3)), 'owner_id' => $owner->id]);
        $drivers = collect($driverNames)->map(fn ($name) => User::factory()->create(['name' => $name]));

        ChampionshipRegistration::create([
            'championship_id' => $this->championship->id, 'user_id' => $owner->id,
            'racing_team_id' => $racingTeam->id, 'car_number' => $number,
            'championship_class_id' => $class?->id, 'driver_ids' => $drivers->pluck('id')->all(),
        ]);
        $entry = RaceTeamEntry::create(['race_id' => $this->round->id, 'racing_team_id' => $racingTeam->id, 'car_number' => $number]);
        foreach ($drivers as $driver) {
            RaceRegistration::create(['race_id' => $this->round->id, 'user_id' => $driver->id, 'team_entry_id' => $entry->id]);
        }
    }

    // [heading => box text] for every roster box on the round's event page.
    private function rosterBoxes(): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($this->get(route('events.show', $this->round))->assertOk()->getContent());
        $xpath = new \DOMXPath($dom);

        $boxes = [];
        foreach ($xpath->query('//*[contains(@class, "xcl-ev-roster")]') as $box) {
            $heading = $xpath->query('.//h3', $box)->item(0);
            $boxes[trim(preg_replace('/\s+/', ' ', $heading->firstChild->textContent))] = $box->textContent;
        }

        return $boxes;
    }

    public function test_each_class_gets_its_own_box_with_its_own_cars(): void
    {
        $gt3 = ChampionshipClass::create(['championship_id' => $this->championship->id, 'name' => 'GT3', 'sort_order' => 0, 'max_drivers' => 13]);
        $gt4 = ChampionshipClass::create(['championship_id' => $this->championship->id, 'name' => 'GT4', 'sort_order' => 1]);
        $this->enterCar('Apex Racing', 11, $gt3, ['Jan Jansen', 'Piet Pietersen']);
        $this->enterCar('Blue Motorsport', 22, $gt4, ['Kees de Vries', 'Henk Bakker']);

        $boxes = $this->rosterBoxes();

        $this->assertSame(['GT3', 'GT4'], array_keys($boxes));
        $this->assertStringContainsString('1/13', $boxes['GT3']);
        $this->assertStringContainsString('Jan Jansen', $boxes['GT3']);
        $this->assertStringNotContainsString('Kees de Vries', $boxes['GT3']);
        $this->assertStringContainsString('Kees de Vries', $boxes['GT4']);
        $this->assertStringNotContainsString('Piet Pietersen', $boxes['GT4']);
    }

    public function test_a_car_without_a_class_is_listed_in_its_own_box(): void
    {
        $gt3 = ChampionshipClass::create(['championship_id' => $this->championship->id, 'name' => 'GT3', 'sort_order' => 0]);
        $this->enterCar('Apex Racing', 11, $gt3, ['Jan Jansen', 'Piet Pietersen']);
        $this->enterCar('Blue Motorsport', 22, null, ['Kees de Vries', 'Henk Bakker']);

        $boxes = $this->rosterBoxes();

        $this->assertSame(['GT3', 'NO CLASS'], array_keys($boxes));
        $this->assertStringContainsString('Kees de Vries', $boxes['NO CLASS']);
    }

    public function test_a_single_class_championship_keeps_one_teams_box(): void
    {
        $this->championship->update(['is_multiclass' => false]);
        $this->enterCar('Apex Racing', 11, null, ['Jan Jansen', 'Piet Pietersen']);
        $this->enterCar('Blue Motorsport', 22, null, ['Kees de Vries', 'Henk Bakker']);

        $boxes = $this->rosterBoxes();

        $this->assertSame(['TEAMS'], array_keys($boxes));
        $this->assertStringContainsString('Jan Jansen', $boxes['TEAMS']);
        $this->assertStringContainsString('Kees de Vries', $boxes['TEAMS']);
    }
}
