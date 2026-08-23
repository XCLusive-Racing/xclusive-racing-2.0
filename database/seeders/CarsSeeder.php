<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('cars')->delete();

        $cars = [];
        $id   = 1;

        $acc = [
            'GT3' => [
                'Aston Martin AMR V8 Vantage GT3',
                'Aston Martin Vantage GT3',
                'Aston Martin Vantage V12 GT3',
                'Audi R8 LMS',
                'Audi R8 LMS Evo',
                'Audi R8 LMS GT3 Evo (Cup)',
                'Audi R8 LMS GT3 Evo 2',
                'Bentley Continental GT3 2016',
                'Bentley Continental GT3 2018',
                'BMW M4 GT3',
                'BMW M6 GT3',
                'Ferrari 296 GT3',
                'Ferrari 296 GT3 Evo',
                'Ferrari 488 GT3',
                'Ferrari 488 GT3 Evo',
                'Ford Mustang GT3',
                'Honda NSX GT3',
                'Honda NSX GT3 Evo',
                'Lamborghini Gallardo R-EX',
                'Lamborghini Huracán GT3',
                'Lamborghini Huracán GT3 Evo',
                'Lamborghini Huracán GT3 Evo 2',
                'Lexus RC F GT3',
                'McLaren 650S GT3',
                'McLaren 720S GT3',
                'McLaren 720S GT3 Evo',
                'McLaren 720S GT3 Evo 2',
                'Mercedes-AMG GT3',
                'Mercedes-AMG GT3 2020',
                'Nissan GT-R Nismo GT3 2017',
                'Nissan GT-R Nismo GT3 2018',
                'Porsche 991 II GT3 R',
                'Porsche 992 GT3 R',
                'Porsche 992 GT3 R Evo',
            ],
            'GT4' => [
                'Alpine A110 GT4',
                'Aston Martin Vantage GT4',
                'Audi R8 LMS GT4',
                'Audi R8 LMS GT4 Evo',
                'BMW M4 GT4',
                'BMW M4 GT4 2021',
                'Chevrolet Camaro GT4.R',
                'Ginetta G55 GT4',
                'KTM X-Bow GT4',
                'Maserati MC GT4',
                'McLaren 570S GT4',
                'Mercedes-AMG GT4',
                'Porsche 718 Cayman GT4 Clubsport MR',
            ],
            'GT2' => [
                'Audi R8 LMS GT2',
                'Ferrari 488 GT2',
                'KTM X-Bow GT2',
                'Lamborghini Huracán GT2',
                'Porsche 911 GT2 RS CS Evo',
            ],
            'TCX' => [
                'BMW M2 Club Sport Racing',
            ],
            'GTC' => [
                'Ferrari 488 Challenge Evo',
                'Lamborghini Huracán SuperTrofeo',
                'Porsche 911 GT3 Cup',
            ],
        ];

        foreach ($acc as $class => $names) {
            foreach ($names as $name) {
                $cars[] = ['id' => $id++, 'game' => 'acc', 'car_class' => $class, 'name' => $name, 'year' => null, 'logo' => null, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        DB::table('cars')->insert($cars);
    }
}
