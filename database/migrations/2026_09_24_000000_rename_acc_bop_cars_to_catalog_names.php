<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The console BOP rows were imported with free-form car names ("BMW M4 GT3", "Jaguar G3")
// that don't match any name AccCarCatalog knows, and the bop.json push resolves a car ID
// by exact name -- so 42 of the 50 cars were silently skipped on every push. Renames them
// to the catalogue's names. Names with no known ACC car behind them are left untouched
// (they keep being skipped, and are reported by the push as such).
return new class extends Migration
{
    private const RENAMES = [
        'Aston Martin Vantage V12 GT3' => 'AMR V12 Vantage GT3 (2013)',
        'Aston Martin AMR V8 Vantage GT3' => 'AMR V8 Vantage (2019)',
        'Audi R8 LMS' => 'Audi R8 LMS (2015)',
        'Audi R8 LMS Evo' => 'Audi R8 LMS Evo (2019)',
        'Audi R8 LMS GT3 Evo 2' => 'Audi R8 LMS GT3 Evo 2 (2022)',
        'Bentley Continental GT3 2016' => 'Bentley Continental GT3 (2015)',
        'Bentley Continental GT3 2018' => 'Bentley Continental GT3 (2018)',
        'BMW M4 GT3' => 'BMW M4 GT3 (2022)',
        'BMW M6 GT3' => 'BMW M6 GT3 (2017)',
        'Ferrari 296 GT3' => 'Ferrari 296 GT3 (2023)',
        'Ferrari 488 GT3' => 'Ferrari 488 GT3 (2018)',
        'Ferrari 488 GT3 Evo' => 'Ferrari 488 GT3 Evo (2020)',
        'Ford Mustang GT3' => 'Ford Mustang GT3 (2024)',
        'Honda NSX GT3' => 'Honda NSX GT3 (2017)',
        'Honda NSX GT3 Evo' => 'Honda NSX GT3 Evo (2019)',
        'Jaguar G3' => 'Emil Frey Jaguar G3 (2012)',
        'Lamborghini Gallardo R-EX' => 'Reiter Engineering R-EX GT3 (2017)',
        'Lamborghini Huracán GT3' => 'Lamborghini Huracan GT3 (2015)',
        'Lamborghini Huracán GT3 Evo' => 'Lamborghini Huracan GT3 Evo (2019)',
        'Lamborghini Huracán GT3 Evo 2' => 'Lamborghini Huracan GT3 Evo 2 (2023)',
        'Lexus RC F GT3' => 'Lexus RC F GT3 (2016)',
        'McLaren 650S GT3' => 'McLaren 650S GT3 (2015)',
        'McLaren 720S GT3' => 'McLaren 720S GT3 (2019)',
        'McLaren 720S GT3 Evo' => 'McLaren 720S GT3 Evo (2023)',
        'Mercedes-AMG GT3' => 'Mercedes-AMG GT3 (2015)',
        'Mercedes-AMG GT3 2020' => 'Mercedes-AMG GT3 (2020)',
        'Nissan GT-R Nismo GT3 2017' => 'Nissan GT-R Nismo GT3 (2015)',
        'Nissan GT-R Nismo GT3 2018' => 'Nissan GT-R Nismo GT3 (2018)',
        'Porsche 991 II GT3 R' => 'Porsche 911 II GT3 R (2019)',
        'Porsche 992 GT3 R' => 'Porsche 992 GT3 R (2023)',
        'Alpine A110 GT4' => 'Alpine A110 GT4 (2018)',
        'Aston Martin Vantage GT4' => 'Aston Martin Vantage GT4 (2018)',
        'Audi R8 LMS GT4' => 'Audi R8 LMS GT4 (2018)',
        'BMW M4 GT4' => 'BMW M4 GT4 (2018)',
        'Chevrolet Camaro GT4.R' => 'Chevrolet Camaro GT4 (2017)',
        'Ginetta G55 GT4' => 'Ginetta G55 GT4 (2012)',
        'KTM X-Bow GT4' => 'KTM X-Bow GT4 (2016)',
        'Maserati MC GT4' => 'Maserati MC GT4 (2016)',
        'McLaren 570S GT4' => 'McLaren 570S GT4 (2016)',
        'Mercedes-AMG GT4' => 'Mercedes AMG GT4 (2016)',
        'Porsche 718 Cayman GT4 Clubsport MR' => 'Porsche 718 Cayman GT4 Clubsport (2019)',
        'BMW M2 Club Sport Racing' => 'BMW M2 Club Sport Racing (2022)',
        'Ferrari 488 Challenge Evo' => 'Ferrari 488 Challenge Evo (2022)',
        'Lamborghini Huracán SuperTrofeo' => 'Lamborghini Huracán SuperTrofeo (2015)',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $from => $to) {
            DB::table('bops')->where('game', 'acc')->where('car_model', $from)->update(['car_model' => $to]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $from => $to) {
            DB::table('bops')->where('game', 'acc')->where('car_model', $to)->update(['car_model' => $from]);
        }
    }
};
