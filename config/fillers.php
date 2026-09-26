<?php

// Grid-filler drivers (users.is_filler). `php artisan fillers:seed` creates one user per
// gamertag below that doesn't exist yet; removing a name here doesn't delete its user.
return [

    // One filler shown for every this many real sign-ups (4 → 5, 8 → 10, 12 → 15...).
    'per_real_drivers' => 4,

    'gamertags' => [
        'ApexHunter_77', 'LateBrakeLarry', 'xDriftKing', 'NightStint', 'SlipstreamSam',
        'Kerb_Crusher', 'GT3_Ghost', 'TyreWhisperer', 'RedMist_R', 'PitWallPete',
        'SectorPurple', 'FullSendFinn', 'OversteerOllie', 'TracklimitTom', 'BlueFlagBen',
        'HotlapHenk', 'Chicane_Charlie', 'MaxAttack_M', 'EauRougeEd', 'VortexRacing99',
        'TurboTimo', 'RainMaster_NL', 'DeltaPositive', 'UnderCutUwe', 'SundaySprinter',
        'Paddock_Pixie', 'CarbonCorsa', 'LockUpLuca', 'GripLevelZero', 'SafetyCarSteve',
        'Brake_Bias_B', 'Throttle_Thijs', 'MidnightMonza', 'CopseCorner', 'Nordschleifer',
        'QuickQuali_Q', 'Sideways_Sven', 'StintKing_DK', 'GravelTrapGary', 'FlatOut_Fabio',
    ],

];
