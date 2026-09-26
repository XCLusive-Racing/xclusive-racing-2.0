<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\League;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\User;
use App\Settings\ChampionshipSettingsSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// League feedback 2026-09: finishing points scale with race length against a
// 60-minute race — 30 min = ×0.5, 90 min = ×1.5.
class LengthScaledPointsTest extends TestCase
{
    use RefreshDatabase;

    private function makeChampionship(bool $scaled): Championship
    {
        $league = League::create([
            'name' => 'NLRL', 'slug' => 'nlrl',
            'primary_color' => '#7c3aed', 'accent_color' => '#db2777', 'status' => 'active',
        ]);

        $championship = Championship::create([
            'league_id' => $league->id, 'name' => 'Test Cup', 'game' => 'acc', 'season' => 2026,
            'status' => 'running', 'settings' => ChampionshipSettingsSchema::defaults(),
            'points_system' => [25, 18, 15], 'bonus_fastest_lap' => 1,
        ]);
        $championship->settings = array_replace_recursive($championship->settings->toArray(), [
            'scoring' => ['points_scale_with_length' => $scaled],
        ]);
        $championship->save();

        return $championship;
    }

    private function finishedRound(Championship $championship, int $roundNumber, array $attributes): Race
    {
        return Race::create([
            'championship_id' => $championship->id, 'round_number' => $roundNumber,
            'title' => 'Round '.$roundNumber, 'track' => 'Monza', 'game' => 'acc',
            'status' => 'finished', 'scheduled_at' => now()->subWeek(),
        ] + $attributes);
    }

    private function makeResult(Race $race, User $user, int $position, int $raceNumber = 1, bool $fastestLap = false): void
    {
        RaceResult::create([
            'race_id' => $race->id, 'session_type' => 'race', 'race_number' => $raceNumber, 'user_id' => $user->id,
            'driver_name' => $user->name, 'position' => $position, 'lap_count' => 20, 'fastest_lap' => $fastestLap,
            'dnf' => false, 'dns' => false, 'dsq' => false, 'dc' => false,
        ]);
    }

    private function totals(Championship $championship): array
    {
        return collect($championship->computeStandings())
            ->mapWithKeys(fn ($entry) => [$entry['user']->name => (float) $entry['total_points']])
            ->all();
    }

    public function test_points_scale_with_each_rounds_race_length(): void
    {
        $championship = $this->makeChampionship(scaled: true);
        $a = User::factory()->create(['name' => 'A']);
        $b = User::factory()->create(['name' => 'B']);

        $sprint = $this->finishedRound($championship, 1, ['race_duration' => 30]);
        $this->makeResult($sprint, $a, 1);   // 25 × 0.5 = 12.5
        $this->makeResult($sprint, $b, 2);   // 18 × 0.5 = 9

        $long = $this->finishedRound($championship, 2, ['race_duration' => 90]);
        $this->makeResult($long, $b, 1);     // 25 × 1.5 = 37.5
        $this->makeResult($long, $a, 2);     // 18 × 1.5 = 27

        $this->assertEquals(['B' => 46.5, 'A' => 39.5], $this->totals($championship));
    }

    public function test_bonus_points_are_not_scaled(): void
    {
        $championship = $this->makeChampionship(scaled: true);
        $a = User::factory()->create(['name' => 'A']);

        $sprint = $this->finishedRound($championship, 1, ['race_duration' => 30]);
        $this->makeResult($sprint, $a, 1, fastestLap: true);   // 12.5 + 1

        $this->assertEquals(['A' => 13.5], $this->totals($championship));
    }

    public function test_a_multi_race_round_scales_each_race_by_its_own_length(): void
    {
        $championship = $this->makeChampionship(scaled: true);
        $a = User::factory()->create(['name' => 'A']);

        $weekend = $this->finishedRound($championship, 1, ['race_duration' => 30, 'race_durations' => [30, 60]]);
        $this->makeResult($weekend, $a, 1, raceNumber: 1);   // 12.5
        $this->makeResult($weekend, $a, 1, raceNumber: 2);   // 25

        $this->assertEquals(['A' => 37.5], $this->totals($championship));
    }

    public function test_points_are_unscaled_when_the_setting_is_off(): void
    {
        $championship = $this->makeChampionship(scaled: false);
        $a = User::factory()->create(['name' => 'A']);

        $sprint = $this->finishedRound($championship, 1, ['race_duration' => 30]);
        $this->makeResult($sprint, $a, 1);

        $this->assertEquals(['A' => 25.0], $this->totals($championship));
    }
}
