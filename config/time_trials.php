<?php

// Time Trials leaderboards. Each board is a separate leaderboard that is never merged with
// another, since lap times across platforms are not comparable. Adding the PC server is a
// matter of its laps arriving with platform 'pc'; set 'enabled' => false to hide a board.
return [
    'boards' => [
        'console' => [
            'label' => 'Console',
            'platforms' => ['xbox', 'playstation'],
            'enabled' => true,
        ],
        'pc' => [
            'label' => 'PC',
            'platforms' => ['pc'],
            'enabled' => true,
        ],
    ],

    'default_board' => 'console',

    // Leaderboard rows per page on a track page.
    'per_page' => 100,
];
