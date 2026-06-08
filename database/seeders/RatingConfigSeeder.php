<?php

namespace Database\Seeders;

use App\Models\RatingConfig;
use Illuminate\Database\Seeder;

class RatingConfigSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'K_FACTOR'        => 50.0,
            'STARTING_RATING' => 1500.0,
            'STOP_LOSS_FLOOR' => 500.0,
            'MIN_DRIVERS'     => 8.0,
            'R_HIGH'          => 1.18,
            'R_LOW'           => -0.85,
        ];

        foreach ($defaults as $key => $value) {
            RatingConfig::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}