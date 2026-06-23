<?php

namespace Database\Seeders;

use App\Models\EventFormat;
use Illuminate\Database\Seeder;

class EventFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            ['game' => 'acc', 'name' => 'Super Sprint',       'formation_type' => 'Short',               'practice_mins' => 2,  'quali_mins' => 5,  'race1_mins' => 20,  'pitstop_type' => 'none',     'pitstop_count' => 0, 'min_stop_secs' => null, 'xcl_r_multiplier' => 0.6, 'server_preference' => '1/2', 'sort_order' => 1],
            ['game' => 'acc', 'name' => 'Sprint Race',        'formation_type' => 'Short',               'practice_mins' => 2,  'quali_mins' => 10, 'race1_mins' => 30,  'pitstop_type' => 'none',     'pitstop_count' => 0, 'min_stop_secs' => null, 'xcl_r_multiplier' => 0.8, 'server_preference' => '1/2', 'sort_order' => 2],
            ['game' => 'acc', 'name' => 'Daily Race',         'formation_type' => 'Full - Nords (short)', 'practice_mins' => 5,  'quali_mins' => 12, 'race1_mins' => 30,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => null, 'xcl_r_multiplier' => 1.0, 'server_preference' => '1/2', 'sort_order' => 3],
            ['game' => 'acc', 'name' => 'Double Sprint',      'formation_type' => 'Short',               'practice_mins' => 5,  'quali_mins' => 10, 'race1_mins' => 20,  'quali2_mins' => 10, 'race2_mins' => 20,  'pitstop_type' => 'none',     'pitstop_count' => 0, 'min_stop_secs' => null, 'xcl_r_multiplier' => 1.2, 'server_preference' => '3',   'sort_order' => 4],
            ['game' => 'acc', 'name' => 'Intermediate Race',  'formation_type' => 'Full - Nords (short)', 'practice_mins' => 5,  'quali_mins' => 15, 'race1_mins' => 45,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 1.5, 'server_preference' => '3',   'sort_order' => 5],
            ['game' => 'acc', 'name' => 'Full Race',          'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 15, 'race1_mins' => 60,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 2.0, 'server_preference' => '3',   'sort_order' => 6],
            ['game' => 'acc', 'name' => 'Double Race',        'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 15, 'race1_mins' => 30,  'quali2_mins' => 15, 'race2_mins' => 30,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 2.1, 'server_preference' => '4',   'sort_order' => 7],
            ['game' => 'acc', 'name' => 'Multiclass',         'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 20, 'race1_mins' => 60,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 2.2, 'server_preference' => '4',   'sort_order' => 8],
            ['game' => 'acc', 'name' => 'Long Race',          'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 15, 'race1_mins' => 90,  'pitstop_type' => 'fuel_only', 'pitstop_count' => 1, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 2.4, 'server_preference' => '4',   'sort_order' => 9],
            ['game' => 'acc', 'name' => 'Mini Enduro',        'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 20, 'race1_mins' => 120, 'pitstop_type' => 'fuel_only', 'pitstop_count' => 2, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 2.5, 'server_preference' => '4',   'sort_order' => 10],
            ['game' => 'acc', 'name' => 'Endurance',          'formation_type' => 'Full - Nords (short)', 'practice_mins' => 10, 'quali_mins' => 20, 'race1_mins' => 240, 'pitstop_type' => 'fuel_only', 'pitstop_count' => 2, 'min_stop_secs' => 25,   'xcl_r_multiplier' => 3.0, 'server_preference' => '4',   'sort_order' => 11],
        ];

        foreach ($formats as $data) {
            EventFormat::firstOrCreate(['game' => $data['game'], 'name' => $data['name']], $data);
        }
    }
}
