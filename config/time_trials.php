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

    // Weekly event server loop: a short practice, then qualifying, repeated by ACC itself.
    'practice_minutes' => 2,
    'qualifying_minutes' => 30,

    // How often the event's config and entry list are uploaded again. An upload never
    // kicks anyone: the server only reads it when it restarts on its own.
    'push_every_minutes' => 60,
];
