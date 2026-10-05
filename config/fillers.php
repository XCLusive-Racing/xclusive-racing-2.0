<?php

// Grid-filler drivers (users.is_filler). `php artisan fillers:seed` creates one user per
// gamertag below that doesn't exist yet; removing a name here doesn't delete its user.
return [

    // One filler shown for every this many real sign-ups (4 → 5, 8 → 10, 12 → 15...).
    'per_real_drivers' => 4,

    // Spots fillers always leave free: once real drivers plus fillers reach the cap minus
    // this many (45 of 50, 30 of 35), every new real sign-up makes one filler drop out, so
    // fillers never push anyone onto the waiting list.
    'free_spot_margin' => 5,

    'gamertags' => [
        'ApexHunter_77', 'LateBr4ke03', 'xDriftKing', 'NightStint', 'SamsStreamzZ',
        'Kerb_Crusher', 'GT3_Ghost', 'John2voelta', 'RedMist_R', 'llPitwallPetyll',
        'SectorPurple', 'Fullyzendsit01', 'OversteerOllie', 'TracklimitTom', 'BlueFlagBen',
        'HotlapHenk', 'ChicoChican3', 'Max01Ver-33', 'EauRougeEd', 'VortexRacing99',
        'TurboTimo3666', 'RainMaster_NL', 'DeltaPositive', 'UnderCutUwe', 'SundaySprinter',
        'Paddie-Pixie', 'CarbonCorsa', 'MuleLuca', 'GripLevelZero', 'Stev0car',
        'Biazed0387', 'ThijsBNL', 'MidnightMonza', 'CopseCorner', 'NordzGHsleifer',
        'quickerquicks1956', 'SvnSevn777', 'StintKing_DK', 'GravelTrapGary', 'FlatearthN1',

        // 2026-10: ordinary gamertags, nothing racing-themed, so fillers don't stand out.
        // A name ending in a country code (JoseG_ES, fallutNL) gets that country.
        'JoseG_ES', 'KihikiWhat', 'Noneed48B', 'AryIs01_BE', 'GreatGr4nd4d',
        'IKik67', 'th3Outerkid', 'SnipzsCopper', 'Brodonewhut778', 'hisname_DK',
        'ultimaterides456', 'Sabrina991Fluxi', 'Pixtarminton44', 'fallutNL', 'f3nux43',
        'Agnt74tie', 'Orybambooz', 'cred1tar3', 'SjevsjamGB', 'Jammeryk',
        'MarcoV_IT', 'lukaszB_PL', 'Tobbe_SE', 'RenzoK77', 'nightowl_vince',
        'Kevkev2003', 'DaanvD_NL', 'jorisgamer88', 'Mikkel_DK', 'Frenchie_Loic',
        'Bastiii98', 'PedroMtz_ES', 'Sven1987', 'quirkyjoe', 'Lennert_BE',
        'xXwolfieXx', 'Zebbe04', 'Kuba_PL', 'TheRealDennis', 'Mattiee_FR',
        'rubenzz12', 'olliebear_GB', 'Jakeyy_US', 'Fl0rian_DE', 'ghostlyfin',
        'Noahh_NL', 'Kristof91', 'mrPotato33', 'Emilia_SE', 'HugoDLC',
        'thijsvh', 'Yannick_BE', 'brokenheadset', 'SilentMika', 'LeoTheLion09',
        'Arjen1976', 'Willemsz', 'CoffeeAndChill', 'Rico_IT', 'TomaszK21',
    ],

];
