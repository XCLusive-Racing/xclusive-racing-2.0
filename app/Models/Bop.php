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

    public static function carModels(): array
    {
        return [
            0  => 'Porsche 991 GT3 R',
            1  => 'Mercedes-AMG GT3',
            2  => 'Ferrari 488 GT3',
            3  => 'Audi R8 LMS',
            4  => 'Lamborghini Huracán GT3',
            5  => 'McLaren 650S GT3',
            6  => 'Nissan GT-R Nismo GT3 2018',
            7  => 'BMW M6 GT3',
            8  => 'Bentley Continental GT3 2018',
            9  => 'Porsche 991 II GT3 Cup',
            10 => 'Nissan GT-R Nismo GT3 2017',
            11 => 'Bentley Continental GT3 2016',
            12 => 'Aston Martin Vantage V12 GT3',
            13 => 'Lamborghini Gallardo R-EX',
            14 => 'Jaguar G3',
            15 => 'Lexus RC F GT3',
            16 => 'Lamborghini Huracán GT3 Evo',
            17 => 'Honda NSX GT3',
            18 => 'Lamborghini Huracán SuperTrofeo',
            19 => 'Audi R8 LMS Evo',
            20 => 'Aston Martin AMR V8 Vantage GT3',
            21 => 'Honda NSX GT3 Evo',
            22 => 'McLaren 720S GT3',
            23 => 'Porsche 991 II GT3 R',
            24 => 'Ferrari 488 GT3 Evo',
            25 => 'Mercedes-AMG GT3 2020',
            26 => 'Ferrari 488 Challenge Evo',
            27 => 'BMW M2 Club Sport Racing',
            28 => 'Porsche 992 GT3 Cup',
            29 => 'Lamborghini Huracán SuperTrofeo EVO2',
            30 => 'BMW M4 GT3',
            31 => 'Audi R8 LMS GT3 Evo 2',
            32 => 'Ferrari 296 GT3',
            33 => 'Lamborghini Huracán GT3 Evo 2',
            34 => 'Porsche 992 GT3 R',
            35 => 'McLaren 720S GT3 Evo',
            36 => 'Ford Mustang GT3',
            50 => 'Alpine A110 GT4',
            51 => 'Aston Martin Vantage GT4',
            52 => 'Audi R8 LMS GT4',
            53 => 'BMW M4 GT4',
            55 => 'Chevrolet Camaro GT4.R',
            56 => 'Ginetta G55 GT4',
            57 => 'KTM X-Bow GT4',
            58 => 'Maserati MC GT4',
            59 => 'McLaren 570S GT4',
            60 => 'Mercedes-AMG GT4',
            61 => 'Porsche 718 Cayman GT4 Clubsport MR',
            80 => 'Audi R8 LMS GT3 Evo (Cup)',
            82 => 'BMW M4 GT4 2021',
            83 => 'Audi R8 LMS GT4 Evo',
            84 => 'Ferrari 296 GT3 Evo',
            85 => 'McLaren 720S GT3 Evo 2',
            86 => 'Porsche 992 GT3 R Evo',
        ];
    }

    public static function carModelId(string $name): ?int
    {
        return array_search($name, self::carModels(), true) !== false
            ? array_search($name, self::carModels(), true)
            : null;
    }
}