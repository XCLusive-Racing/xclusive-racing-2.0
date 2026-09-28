<?php

// Grid-filler drivers (users.is_filler). `php artisan fillers:seed` creates one user per
// gamertag below that doesn't exist yet; removing a name here doesn't delete its user.
return [

    // One filler shown for every this many real sign-ups (4 → 5, 8 → 10, 12 → 15...).
    'per_real_drivers' => 4,

    'gamertags' => [
        'ApexHunter_77', 'LateBr4ke03', 'xDriftKing', 'NightStint', 'SamsStreamzZ',
        'Kerb_Crusher', 'GT3_Ghost', 'John2voelta', 'RedMist_R', 'llPitwallPetyll',
        'SectorPurple', 'Fullyzendsit01', 'OversteerOllie', 'TracklimitTom', 'BlueFlagBen',
        'HotlapHenk', 'ChicoChican3', 'MaxAttack_M', 'EauRougeEd', 'VortexRacing99',
        'TurboTimo3666', 'RainMaster_NL', 'DeltaPositive', 'UnderCutUwe', 'SundaySprinter',
        'Paddock_Pixie', 'CarbonCorsa', 'MuleLuca', 'GripLevelZero', 'Thacarman_steve',
        'Biazed0387', 'ThijsBNL', 'MidnightMonza', 'CopseCorner', 'NordzGHsleifer',
        'quickerquicks1956', 'SvnSevn777', 'StintKing_DK', 'GravelTrapGary', 'FlatearthNFlatout',
    ],

];
