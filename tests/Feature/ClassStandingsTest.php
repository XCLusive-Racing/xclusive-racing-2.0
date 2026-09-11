<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipClass;
use App\Models\ChampionshipRegistration;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Refinement request item 6: re-verify Championship::computeClassStandings()
// still works correctly now that buildDriverStandings() (which it groups)
// also computes missed-rounds penalties and reads from a per-league points
// scheme — neither existed when class standings were first built. No test
// covered this method at all before now.
class ClassStandingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeague(string $slug): League
    {
        return League::create([
            'name' => strtoupper($slug), 'slug' => $slug,
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);
    }

    private function makeChampionship(League $league, array $overrides = []): Championship
    {
        return Championship::create(array_merge([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'registration_open', 'is_multiclass' => true, 'settings' => ChampionshipSettingsSchema::defaults(),
        ], $overrides));
    }

    private function finishedRound(Championship $championship, int $roundNumber): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $roundNumber,
            'title' => 'Round ' . $roundNumber, 'track' => 'Monza', 'game' => 'acc',
            'status' => 'finished', 'scheduled_at' => now()->subWeeks(4 - $roundNumber),
        ]);
    }

    private function makeResult(Race $race, User $user, int $position): RaceResult
    {
        return RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'user_id' => $user->id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    public function test_drivers_are_grouped_into_their_own_class_preserving_overall_points(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->points_system = [25, 18, 15];
        $championship->save();

        $pro = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Pro', 'max_drivers' => 20]);
        $am  = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Am', 'max_drivers' => 20]);

        $proDriver = User::factory()->create();
        $amDriver  = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $proDriver->id, 'championship_class_id' => $pro->id]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $amDriver->id, 'championship_class_id' => $am->id]);

        $race = $this->finishedRound($championship, 1);
        // Am driver crosses the line ahead overall (P1), Pro driver P2 — class
        // standings must still show each in their own class, not overall order.
        $this->makeResult($race, $amDriver, 1);
        $this->makeResult($race, $proDriver, 2);

        $classStandings = $championship->computeClassStandings();

        $this->assertSame(['Pro', 'Am'], array_values(array_map(fn ($g) => $g['class']->name, $classStandings)));
        $this->assertCount(1, $classStandings[$pro->id]['standings']);
        $this->assertSame($proDriver->id, $classStandings[$pro->id]['standings'][0]['user_id']);
        $this->assertSame(18.0, (float) $classStandings[$pro->id]['standings'][0]['total_points']);
        $this->assertCount(1, $classStandings[$am->id]['standings']);
        $this->assertSame($amDriver->id, $classStandings[$am->id]['standings'][0]['user_id']);
        $this->assertSame(25.0, (float) $classStandings[$am->id]['standings'][0]['total_points']);
    }

    public function test_a_driver_with_no_class_assignment_is_omitted_from_every_group(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $pro = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Pro', 'max_drivers' => 20]);

        $unclassed = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $unclassed->id]); // no class

        $classStandings = $championship->computeClassStandings();

        $this->assertCount(0, $classStandings[$pro->id]['standings']);
    }

    public function test_class_standings_reflect_a_missed_rounds_penalty_from_the_same_driver_standings_build(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['max_missed_rounds' => 0, 'missed_rounds_action' => 'penalise', 'missed_rounds_penalty_points' => 5],
        ]);
        $championship->save();

        $pro = ChampionshipClass::create(['championship_id' => $championship->id, 'name' => 'Pro', 'max_drivers' => 20]);
        $driver = User::factory()->create();
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => $driver->id, 'championship_class_id' => $pro->id]);

        $this->finishedRound($championship, 1); // missed, 0 allowed -> -5 penalty

        $classStandings = $championship->computeClassStandings();

        $this->assertSame(5, $classStandings[$pro->id]['standings'][0]['missed_rounds_penalty']);
        $this->assertSame(-5.0, (float) $classStandings[$pro->id]['standings'][0]['total_points']);
    }

    public function test_class_standings_are_empty_for_a_single_class_championship(): void
    {
        $league       = $this->makeLeague('nlrl');
        $championship = $this->makeChampionship($league, ['is_multiclass' => false]);
        ChampionshipRegistration::create(['championship_id' => $championship->id, 'user_id' => User::factory()->create()->id]);

        $this->assertSame([], $championship->computeClassStandings());
    }
}
