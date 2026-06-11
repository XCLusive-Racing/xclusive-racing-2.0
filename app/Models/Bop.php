<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bop extends Model
{
    protected $fillable = ['game', 'car_model', 'track', 'ballast_kg', 'restrictor', 'notes'];

    public static function games(): array
    {
        return ['acc' => 'ACC', 'lmu' => 'LMU', 'iracing' => 'iRacing', 'ac' => 'AC Rally'];
    }

    public static function categories(): array
    {
        return ['gt3' => 'GT3', 'gt4' => 'GT4', 'gt2' => 'GT2', 'cup' => 'Cup / GTC'];
    }

    public static function carModels(): array
    {
        return [
            // GT3
            0  => 'Porsche 911 GT3 R 2018',
            1  => 'Mercedes-AMG GT3 2015',
            2  => 'Ferrari 488 GT3 2018',
            3  => 'Audi R8 LMS GT3 2015',
            4  => 'Lamborghini Huracán GT3 2015',
            5  => 'McLaren 650S GT3 2015',
            6  => 'Nissan GT-R Nismo GT3 2018',
            7  => 'BMW M6 GT3 2017',
            8  => 'Bentley Continental GT3 2018',
            10 => 'Nissan GT-R Nismo GT3 2015',
            11 => 'Bentley Continental GT3 2015',
            12 => 'Aston Martin V12 Vantage GT3 2013',
            13 => 'Reiter Engineering R-EX GT3 2017',
            14 => 'Emil Frey Jaguar GT3 2012',
            15 => 'Lexus RC F GT3 2016',
            16 => 'Lamborghini Huracán Evo GT3 2019',
            17 => 'Honda NSX GT3 2017',
            19 => 'Audi R8 LMS Evo GT3 2019',
            20 => 'Aston Martin AMR V8 Vantage GT3 2019',
            21 => 'Honda NSX Evo GT3 2019',
            22 => 'McLaren 720S GT3 2019',
            23 => 'Porsche 911 II GT3 R 2019',
            24 => 'Ferrari 488 Evo GT3 2020',
            25 => 'Mercedes-AMG Evo GT3 2020',
            30 => 'BMW M4 GT3 2021',
            31 => 'Audi R8 LMS Evo II GT3 2022',
            32 => 'Ferrari 296 GT3 2023',
            33 => 'Lamborghini Huracán Evo2 GT3 2023',
            34 => 'Porsche 992 GT3 R 2023',
            35 => 'McLaren 720S Evo GT3 2023',
            36 => 'Ford Mustang GT3',
            84 => 'Ferrari 296 GT3 Evo',
            85 => 'McLaren 720S GT3 Evo 2',
            86 => 'Porsche 992 GT3 R Evo',

            // GT4
            50 => 'Alpine A110 GT4 2018',
            51 => 'Aston Martin Vantage GT4 2018',
            52 => 'Audi R8 LMS GT4 2018',
            53 => 'BMW M4 GT4 2018',
            55 => 'Chevrolet Camaro GT4.R 2017',
            56 => 'Ginetta G55 GT4 2012',
            57 => 'KTM X-Bow GT4 2016',
            58 => 'Maserati MC GT4 2016',
            59 => 'McLaren 570S GT4 2016',
            60 => 'Mercedes-AMG GT4 2016',
            61 => 'Porsche 718 Cayman GT4 Clubsport MR 2019',
            82 => 'BMW M4 GT4 2021',
            83 => 'Audi R8 LMS GT4 Evo',

            // GT2 — IDs provisional, verify before using BOP push
            109 => 'Audi R8 LMS GT2',
            112 => 'KTM X-Bow GT2',
            115 => 'Maserati MC20 GT2',
            116 => 'Mercedes-AMG GT2',
            117 => 'Porsche 911 GT2 RS CS Evo',
            118 => 'Porsche 935',

            // Cup / GTC
            9  => 'Porsche 911 II GT3 Cup 2017',
            18 => 'Lamborghini Huracán SuperTrofeo 2015',
            26 => 'Ferrari 488 Challenge Evo 2020',
            27 => 'BMW M2 CS Racing 2020',
            28 => 'Porsche 992 GT3 Cup 2021',
            29 => 'Lamborghini Huracán SuperTrofeo Evo2 2021',
            80 => 'Audi R8 LMS GT3 Evo Cup',
        ];
    }

    public static function carModelCategoryMap(): array
    {
        return [
            'gt3' => [0,1,2,3,4,5,6,7,8,10,11,12,13,14,15,16,17,19,20,21,22,23,24,25,30,31,32,33,34,35,36,84,85,86],
            'gt4' => [50,51,52,53,55,56,57,58,59,60,61,82,83],
            'gt2' => [109,112,115,116,117,118],
            'cup' => [9,18,26,27,28,29,80],
        ];
    }

    public static function carNamesByCategory(string $category): array
    {
        $ids = self::carModelCategoryMap()[$category] ?? [];
        $models = self::carModels();
        return array_values(array_filter(array_map(fn($id) => $models[$id] ?? null, $ids)));
    }

    public static function carModelId(string $name): ?int
    {
        $found = array_search($name, self::carModels(), true);
        return $found !== false ? $found : null;
    }
}