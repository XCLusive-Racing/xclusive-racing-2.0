<?php

namespace App\Services;

// The single source of truth for ACC car model IDs, names and classes -- per platform,
// because ACC Console ('acc') and ACC PC ('ac') number their cars differently (e.g. the
// BMW M4 GT3 is 26 on console but 30 on PC). Every place that turns a carModel ID into a
// name/class (result import, results page) or a name back into an ID (bop.json push, BOP
// JSON import) must go through here with the race/BOP's game, never assume one list.
//
// The same car deliberately carries the same name on both platforms, so race_results.vehicle
// strings (and the BOP rows keyed by name) line up across console and PC.
class AccCarCatalog
{
    // Verified against real console result files (production race_results, 2026-09).
    private const CONSOLE = [
        0 => ['Porsche 991 GT3 R (2018)', 'GT3'],
        1 => ['Mercedes-AMG GT3 (2015)', 'GT3'],
        2 => ['Ferrari 488 GT3 (2018)', 'GT3'],
        3 => ['Audi R8 LMS (2015)', 'GT3'],
        4 => ['Lamborghini Huracan GT3 (2015)', 'GT3'],
        5 => ['McLaren 650S GT3 (2015)', 'GT3'],
        6 => ['Nissan GT-R Nismo GT3 (2018)', 'GT3'],
        7 => ['BMW M6 GT3 (2017)', 'GT3'],
        8 => ['Bentley Continental GT3 (2018)', 'GT3'],
        9 => ['Porsche 991 II GT3 Cup (2017)', 'GTC'],
        10 => ['Nissan GT-R Nismo GT3 (2015)', 'GT3'],
        11 => ['Bentley Continental GT3 (2015)', 'GT3'],
        12 => ['AMR V12 Vantage GT3 (2013)', 'GT3'],
        13 => ['Reiter Engineering R-EX GT3 (2017)', 'GT3'],
        14 => ['Emil Frey Jaguar G3 (2012)', 'GT3'],
        15 => ['Lexus RC F GT3 (2016)', 'GT3'],
        16 => ['Lamborghini Huracan GT3 Evo (2019)', 'GT3'],
        17 => ['Honda NSX GT3 (2017)', 'GT3'],
        18 => ['Lamborghini Huracán SuperTrofeo (2015)', 'GTC'],
        19 => ['Audi R8 LMS Evo (2019)', 'GT3'],
        20 => ['AMR V8 Vantage (2019)', 'GT3'],
        21 => ['Honda NSX GT3 Evo (2019)', 'GT3'],
        22 => ['McLaren 720S GT3 (2019)', 'GT3'],
        23 => ['Porsche 911 II GT3 R (2019)', 'GT3'],
        24 => ['Ferrari 488 GT3 Evo (2020)', 'GT3'],
        25 => ['Mercedes-AMG GT3 (2020)', 'GT3'],
        26 => ['BMW M4 GT3 (2022)', 'GT3'],
        27 => ['Ferrari 488 Challenge Evo (2022)', 'GTC'],
        28 => ['BMW M2 Club Sport Racing (2022)', 'TCX'],
        29 => ['Porsche 992 GT3 Cup (2022)', 'GTC'],
        30 => ['Lamborghini Huracán SuperTrofeo EVO2 (2022)', 'GTC'],
        31 => ['Audi R8 LMS GT3 Evo 2 (2022)', 'GT3'],
        32 => ['Ferrari 296 GT3 (2023)', 'GT3'],
        33 => ['Lamborghini Huracan GT3 Evo 2 (2023)', 'GT3'],
        34 => ['Porsche 992 GT3 R (2023)', 'GT3'],
        35 => ['McLaren 720S GT3 Evo (2023)', 'GT3'],
        36 => ['Ford Mustang GT3 (2024)', 'GT3'],
        50 => ['Alpine A110 GT4 (2018)', 'GT4'],
        51 => ['Aston Martin Vantage GT4 (2018)', 'GT4'],
        52 => ['Audi R8 LMS GT4 (2018)', 'GT4'],
        53 => ['BMW M4 GT4 (2018)', 'GT4'],
        55 => ['Chevrolet Camaro GT4 (2017)', 'GT4'],
        56 => ['Ginetta G55 GT4 (2012)', 'GT4'],
        57 => ['KTM X-Bow GT4 (2016)', 'GT4'],
        58 => ['Maserati MC GT4 (2016)', 'GT4'],
        59 => ['McLaren 570S GT4 (2016)', 'GT4'],
        60 => ['Mercedes AMG GT4 (2016)', 'GT4'],
        61 => ['Porsche 718 Cayman GT4 Clubsport (2019)', 'GT4'],
        80 => ['Audi R8 LMS GT2 (2021)', 'GT2'],
        82 => ['KTM Xbow GT2 (2021)', 'GT2'],
        83 => ['Maserati GT2 (2023)', 'GT2'],
        84 => ['Mercedes AMG GT2 (2023)', 'GT2'],
        85 => ['Porsche 991 II GT2 RS CS EVO (2023)', 'GT2'],
        86 => ['Porsche 935 (2019)', 'GT2'],
    ];

    // From the ACC dedicated server handbook (PC build). NOT yet verified against a real
    // ACC PC result file -- check this list the first time PC results come in.
    private const PC = [
        0 => ['Porsche 991 GT3 R (2018)', 'GT3'],
        1 => ['Mercedes-AMG GT3 (2015)', 'GT3'],
        2 => ['Ferrari 488 GT3 (2018)', 'GT3'],
        3 => ['Audi R8 LMS (2015)', 'GT3'],
        4 => ['Lamborghini Huracan GT3 (2015)', 'GT3'],
        5 => ['McLaren 650S GT3 (2015)', 'GT3'],
        6 => ['Nissan GT-R Nismo GT3 (2018)', 'GT3'],
        7 => ['BMW M6 GT3 (2017)', 'GT3'],
        8 => ['Bentley Continental GT3 (2018)', 'GT3'],
        9 => ['Porsche 991 II GT3 Cup (2017)', 'GTC'],
        10 => ['Nissan GT-R Nismo GT3 (2015)', 'GT3'],
        11 => ['Bentley Continental GT3 (2015)', 'GT3'],
        12 => ['AMR V12 Vantage GT3 (2013)', 'GT3'],
        13 => ['Reiter Engineering R-EX GT3 (2017)', 'GT3'],
        14 => ['Emil Frey Jaguar G3 (2012)', 'GT3'],
        15 => ['Lexus RC F GT3 (2016)', 'GT3'],
        16 => ['Lamborghini Huracan GT3 Evo (2019)', 'GT3'],
        17 => ['Honda NSX GT3 (2017)', 'GT3'],
        18 => ['Lamborghini Huracán SuperTrofeo (2015)', 'GTC'],
        19 => ['Audi R8 LMS Evo (2019)', 'GT3'],
        20 => ['AMR V8 Vantage (2019)', 'GT3'],
        21 => ['Honda NSX GT3 Evo (2019)', 'GT3'],
        22 => ['McLaren 720S GT3 (2019)', 'GT3'],
        23 => ['Porsche 911 II GT3 R (2019)', 'GT3'],
        24 => ['Ferrari 488 GT3 Evo (2020)', 'GT3'],
        25 => ['Mercedes-AMG GT3 (2020)', 'GT3'],
        26 => ['Ferrari 488 Challenge Evo (2022)', 'GTC'],
        27 => ['BMW M2 Club Sport Racing (2022)', 'TCX'],
        28 => ['Porsche 992 GT3 Cup (2022)', 'GTC'],
        29 => ['Lamborghini Huracán SuperTrofeo EVO2 (2022)', 'GTC'],
        30 => ['BMW M4 GT3 (2022)', 'GT3'],
        31 => ['Audi R8 LMS GT3 Evo 2 (2022)', 'GT3'],
        32 => ['Ferrari 296 GT3 (2023)', 'GT3'],
        33 => ['Lamborghini Huracan GT3 Evo 2 (2023)', 'GT3'],
        34 => ['Porsche 992 GT3 R (2023)', 'GT3'],
        35 => ['McLaren 720S GT3 Evo (2023)', 'GT3'],
        36 => ['Ford Mustang GT3 (2024)', 'GT3'],
        50 => ['Alpine A110 GT4 (2018)', 'GT4'],
        51 => ['Aston Martin Vantage GT4 (2018)', 'GT4'],
        52 => ['Audi R8 LMS GT4 (2018)', 'GT4'],
        53 => ['BMW M4 GT4 (2018)', 'GT4'],
        55 => ['Chevrolet Camaro GT4 (2017)', 'GT4'],
        56 => ['Ginetta G55 GT4 (2012)', 'GT4'],
        57 => ['KTM X-Bow GT4 (2016)', 'GT4'],
        58 => ['Maserati MC GT4 (2016)', 'GT4'],
        59 => ['McLaren 570S GT4 (2016)', 'GT4'],
        60 => ['Mercedes AMG GT4 (2016)', 'GT4'],
        61 => ['Porsche 718 Cayman GT4 Clubsport (2019)', 'GT4'],
        80 => ['Audi R8 LMS GT2 (2021)', 'GT2'],
        82 => ['KTM Xbow GT2 (2021)', 'GT2'],
        83 => ['Maserati GT2 (2023)', 'GT2'],
        84 => ['Mercedes AMG GT2 (2023)', 'GT2'],
        85 => ['Porsche 991 II GT2 RS CS EVO (2023)', 'GT2'],
        86 => ['Porsche 935 (2019)', 'GT2'],
    ];

    // BOP page category filter => ACC homologation classes it covers.
    private const CATEGORY_CLASSES = [
        'gt3' => ['GT3'],
        'gt4' => ['GT4'],
        'gt2' => ['GT2'],
        'cup' => ['GTC', 'TCX'],
    ];

    public static function supports(string $game): bool
    {
        return in_array($game, ['acc', 'ac'], true);
    }

    /** id => name for the given game's platform; empty for non-ACC games. */
    public static function cars(string $game): array
    {
        return array_map(fn ($car) => $car[0], self::table($game));
    }

    public static function name(?int $modelId, string $game): ?string
    {
        if ($modelId === null) {
            return null;
        }

        return self::table($game)[$modelId][0] ?? 'Car #'.$modelId;
    }

    public static function carClass(?int $modelId, string $game): ?string
    {
        if ($modelId === null) {
            return null;
        }

        return self::table($game)[$modelId][1] ?? null;
    }

    /** name => class for the given game's platform, sorted by name; empty for non-ACC games. */
    public static function namesWithClass(string $game): array
    {
        $cars = array_column(self::table($game), 1, 0);
        ksort($cars);

        return $cars;
    }

    public static function classOfName(string $name, string $game): ?string
    {
        return self::namesWithClass($game)[$name] ?? null;
    }

    public static function id(string $name, string $game): ?int
    {
        $found = array_search($name, self::cars($game), true);

        return $found !== false ? $found : null;
    }

    /** Car names in a BOP page category (gt3/gt4/gt2/cup) for the given game. */
    public static function namesInCategory(string $category, string $game): array
    {
        $classes = self::CATEGORY_CLASSES[$category] ?? [];

        return array_values(array_map(
            fn ($car) => $car[0],
            array_filter(self::table($game), fn ($car) => in_array($car[1], $classes, true))
        ));
    }

    private static function table(string $game): array
    {
        return match ($game) {
            'acc' => self::CONSOLE,
            'ac' => self::PC,
            default => [],
        };
    }
}
