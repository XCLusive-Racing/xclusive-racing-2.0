<?php

namespace Database\Seeders;

use App\Models\League;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $leagues = [
            ['name' => 'NLRL', 'slug' => 'nlrl', 'primary_color' => '#f97316', 'accent_color' => '#1d4ed8', 'status' => 'draft'],
            ['name' => 'SRC',  'slug' => 'src',  'primary_color' => '#16a34a', 'accent_color' => '#0f172a', 'status' => 'draft'],
            ['name' => 'EER',  'slug' => 'eer',  'primary_color' => '#dc2626', 'accent_color' => '#facc15', 'status' => 'draft'],
        ];

        // Console context, no authenticated user — bypass the tenant scope explicitly.
        foreach ($leagues as $data) {
            League::withoutTenantScope()->firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
