<?php

// XP and achievements (docs/xp-achievements/PLAN.md).
return [
    // Off until launch: with this off no race awards anything, whatever is deployed.
    'enabled' => env('XP_ENABLED', false),

    // Everybody starts from zero at this moment (UK time, e.g. "2026-11-01 00:00"). Nothing
    // from before it earns XP or counts towards an achievement. Not set: nothing counts.
    'launch_at' => env('XP_LAUNCH_AT'),

    // Racing XP, from the "XP per race" sheet. Every line is added up, then the
    // multipliers apply to the whole race total.
    'race' => [
        'completed' => 600,
        'retired' => 200,            // entered, but did not take the flag
        'per_lap' => 25,
        'clean' => 400,              // no penalties
        'all_laps_valid' => 300,
        'lead_lap' => 100,
        // Position bonus: [best position, worst position, xp]
        'positions' => [[1, 1, 1000], [2, 2, 700], [3, 3, 500], [4, 5, 300], [6, 10, 200], [11, 25, 100]],
        'pole' => 400,
        'fastest_lap' => 400,
        'per_position_gained' => 25, // grid to flag
        'first_race_of_day' => 300,  // UK day
        'new_title' => 1000,         // once per title, not multiplied

        'multipliers' => [
            'endurance' => 1.5,
            'championship' => 1.25,
            'nordschleife' => 1.5,
            'supporter' => 1.1,
        ],
    ],
];
