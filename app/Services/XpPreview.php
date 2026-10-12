<?php

namespace App\Services;

// EXAMPLE DATA ONLY — a design preview of the XP reveal on the profile page
// (/profile?xp_preview=1, staff only), ahead of the real feature
// (docs/xp-achievements/PLAN.md, Phase 2). Nothing here reads or writes XP: there is no
// XP data yet. The numbers are real catalogue values (docs/xp-achievements/race-xp.csv,
// achievements.csv, rank-curve.csv), put together into one good race. Delete this class
// and its partial when Phase 2 builds the real thing.
class XpPreview
{
    public static function sample(): array
    {
        // "XP per race" rules, as one race: P2 from sixth on the grid, clean, with the fastest lap.
        $race = [
            ['Race completed', 600, null],
            ['14 laps completed', 350, '14 × 25'],
            ['Clean race, no penalties', 400, null],
            ['All laps valid', 300, null],
            ['Finished on the lead lap', 100, null],
            ['Position bonus — P2', 700, null],
            ['Fastest lap', 400, null],
            ['Positions gained', 100, '4 × 25'],
            ['First race of the day', 300, null],
        ];

        $achievements = [
            ['Podium 10', 'Finish on the podium 10 times', 250, 'Results', 'fa-solid fa-trophy'],
            ['Fastest Lap 5', 'Set 5 race fastest laps', 150, 'Results', 'fa-solid fa-stopwatch'],
            ['Distance 500', 'Complete 500 racing laps', 700, 'Participation', 'fa-solid fa-road'],
            ['Chequered Flag 25', 'Complete 25 races', 300, 'Participation', 'fa-solid fa-flag-checkered'],
        ];

        $before = 30200;
        $gained = array_sum(array_column($race, 1)) + array_sum(array_column($achievements, 2));

        return [
            'race' => ['title' => 'Sprint Race', 'track' => 'Spa', 'result' => 'P2', 'grid' => 'P6'],
            'lines' => array_map(fn (array $l) => ['label' => $l[0], 'xp' => $l[1], 'note' => $l[2]], $race),
            'achievements' => array_map(fn (array $a) => ['name' => $a[0], 'description' => $a[1], 'xp' => $a[2], 'category' => $a[3], 'icon' => $a[4]], $achievements),
            'before' => $before,
            'after' => $before + $gained,
            'gained' => $gained,
            // The part of the rank curve this example moves through: rank => XP it starts at.
            'curve' => [25 => 29275, 26 => 31700, 27 => 34225, 28 => 36850],
        ];
    }

    // [rank, XP into the rank, XP the rank takes] for a total on the sample's curve.
    public static function position(array $curve, int $xp): array
    {
        $ranks = array_keys($curve);
        $rank = $ranks[0];
        foreach ($curve as $r => $at) {
            if ($xp >= $at && isset($curve[$r + 1])) {
                $rank = $r;
            }
        }

        return [$rank, $xp - $curve[$rank], $curve[$rank + 1] - $curve[$rank]];
    }
}
