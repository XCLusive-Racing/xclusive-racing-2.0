<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\LeagueUser;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\RacingTeam;
use App\Models\User;
use App\Services\AccServerConfigService;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Championship review 2026-10-25: rounds kept the car class / cap they were made
// with (wrong server car group and slots), multiclass scored on the overall finish,
// and swapping a class on the Format step silently unregistered its drivers.
class ChampionshipRoundFieldsAndClassScoringTest extends TestCase
{
    use RefreshDatabase;

    private League $league;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
        $this->manager = User::factory()->create();
        LeagueUser::create(['league_id' => $this->league->id, 'user_id' => $this->manager->id, 'role' => 'manager']);
        $this->manager->syncLeagueRoleFlags();
        $this->manager->refresh();
    }

    private function championship(array $attributes = []): Championship
    {
        return Championship::create($attributes + [
            'league_id' => $this->league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'settings' => ChampionshipSettingsSchema::defaults(),
            'points_system' => [25, 18, 15, 12],
        ]);
    }

    private function round(Championship $championship, int $number, string $status = 'open'): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $number, 'title' => 'Round '.$number,
            'track' => 'Monza', 'game' => 'acc', 'status' => $status, 'scheduled_at' => now()->addWeeks($number),
        ]);
    }

    private function raceResult(Race $race, User $user, int $position, array $extra = []): void
    {
        RaceResult::create($extra + [
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id, 'driver_name' => $user->name,
            'position' => $position, 'lap_count' => 20, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    private function saveFormat(Championship $championship, array $format, array $classes = [])
    {
        return $this->actingAs($this->manager)->put(
            route('admin.leagues.championships.wizard.update', [$this->league, $championship, 'format']),
            ['settings' => ['format' => $format]] + ($classes ? ['classes_json' => json_encode($classes)] : [])
        );
    }

    // --- 1. Rounds follow the championship's class and cap ---

    public function test_the_format_step_updates_rounds_still_to_run_but_not_finished_ones(): void
    {
        $championship = $this->championship();
        $upcoming = $this->round($championship, 2);
        $finished = $this->round($championship, 1, 'finished');

        $this->saveFormat($championship, ['car_class' => 'GT3', 'max_entries' => 40])->assertRedirect();

        $upcoming->refresh();
        $this->assertSame('GT3', $upcoming->car_class);
        $this->assertSame(40, (int) $upcoming->max_drivers);
        $this->assertNull($finished->fresh()->car_class);

        $settings = app(AccServerConfigService::class)->settings($upcoming);
        $this->assertSame('GT3', $settings['carGroup']);
        $this->assertSame(40, $settings['maxCarSlots']);
    }

    public function test_a_multiclass_championships_rounds_let_every_class_on_the_server(): void
    {
        $championship = $this->championship(['car_class' => 'GT3']);
        $round = $this->round($championship, 1);

        $this->saveFormat($championship, ['multiclass_enabled' => 1, 'car_class' => 'GT3', 'max_entries' => 45], [
            ['name' => 'GT3', 'max_entries' => null], ['name' => 'GT4', 'max_entries' => null],
        ])->assertRedirect();

        $round->refresh();
        $this->assertTrue((bool) $round->is_multiclass);
        $this->assertNull($round->car_class);
        $this->assertSame('FreeForAll', app(AccServerConfigService::class)->settings($round)['carGroup']);
    }

    public function test_a_new_round_takes_the_championships_class_and_cap(): void
    {
        $championship = $this->championship(['car_class' => 'GT3', 'max_drivers' => 40]);

        $this->actingAs($this->manager)->post(route('admin.leagues.championships.rounds.store', [$this->league, $championship]), [
            'track' => 'Monza', 'scheduled_at' => now()->addWeek()->startOfHour()->tz('Europe/London')->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $round = $championship->rounds()->sole();
        $this->assertSame('GT3', $round->car_class);
        $this->assertSame(40, (int) $round->max_drivers);
    }

    // --- 2. Multiclass scores every class on its own ---

    public function test_each_class_scores_on_its_own_finish_pole_and_fastest_lap(): void
    {
        $championship = $this->championship(['is_multiclass' => true, 'bonus_fastest_lap' => 1, 'bonus_pole' => 1]);
        $gt3 = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'GT3', 'car_class' => 'GT3']);
        $gt4 = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'GT4', 'car_class' => 'GT4']);
        [$a, $b, $c] = User::factory()->count(3)->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $a->id, 'championship_class_id' => $gt3->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $b->id, 'championship_class_id' => $gt3->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $c->id, 'championship_class_id' => $gt4->id]);

        $race = $this->round($championship, 1, 'finished');
        $this->raceResult($race, $a, 1, ['best_lap' => 100000, 'fastest_lap' => true]);
        $this->raceResult($race, $b, 2, ['best_lap' => 100500]);
        $this->raceResult($race, $c, 3, ['best_lap' => 110000]);
        RaceResult::create(['race_id' => $race->id, 'session_type' => 'quali', 'user_id' => $b->id, 'driver_name' => $b->name, 'position' => 1, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false]);
        RaceResult::create(['race_id' => $race->id, 'session_type' => 'quali', 'user_id' => $c->id, 'driver_name' => $c->name, 'position' => 3, 'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false]);

        $points = collect($championship->computeStandings())->pluck('total_points', 'user_id');

        $this->assertEquals(26, $points[$a->id]); // P1 GT3 + fastest GT3 lap
        $this->assertEquals(19, $points[$b->id]); // P2 GT3 + GT3 pole
        $this->assertEquals(27, $points[$c->id]); // P3 overall but P1 GT4, + GT4 pole and fastest lap

        $classes = $championship->computeClassStandings();
        $this->assertSame([$c->id], collect($classes[$gt4->id]['standings'])->pluck('user_id')->all());
    }

    public function test_a_team_cars_drivers_land_in_its_class_not_the_manager(): void
    {
        $championship = $this->championship(['is_multiclass' => true]);
        $gt4 = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'GT4', 'car_class' => 'GT4']);
        $owner = User::factory()->create();
        [$d1, $d2] = User::factory()->count(2)->create();
        $team = RacingTeam::create(['name' => 'Apex Racing', 'tag' => 'APX', 'owner_id' => $owner->id]);
        $team->members()->attach([$d1->id, $d2->id]);
        ChampionshipRegistration::create([
            'championship_id' => $championship->id, 'user_id' => $owner->id, 'racing_team_id' => $team->id,
            'championship_class_id' => $gt4->id, 'driver_ids' => [$d1->id, $d2->id], 'car_number' => 7,
        ]);

        $this->assertSame([$d1->id => $gt4->id, $d2->id => $gt4->id], $championship->carClassByDriver());
    }

    // --- 4. A class with entries can't be swapped away ---

    public function test_a_class_with_entries_cannot_be_removed_on_the_format_step(): void
    {
        $championship = $this->championship(['is_multiclass' => true]);
        $this->saveFormat($championship, ['multiclass_enabled' => 1], [['name' => 'GT3'], ['name' => 'GT4']])->assertRedirect();
        $gt4 = $championship->classes()->where('name', 'GT4')->sole();
        $registration = ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => User::factory()->create()->id, 'championship_class_id' => $gt4->id]);

        $this->saveFormat($championship, ['multiclass_enabled' => 1], [['name' => 'GT3'], ['name' => 'GT2']])
            ->assertSessionHas('error', 'GT4 (1 entry) can\'t be removed while drivers are entered in it. Nothing was saved.');

        $this->assertNotNull($registration->fresh());
        $this->assertSame(['GT3', 'GT4'], $championship->classes()->orderBy('sort_order')->pluck('name')->all());

        // An empty class goes as before.
        $this->saveFormat($championship, ['multiclass_enabled' => 1], [['name' => 'GT4']])->assertRedirect()->assertSessionMissing('error');
        $this->assertSame(['GT4'], $championship->classes()->pluck('name')->all());
    }
}
