<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['name' => 'League Manager', 'slug' => 'league_manager'],
            ['name' => 'League Steward', 'slug' => 'league_steward'],
        ] as $role) {
            if (!DB::table('roles')->where('slug', $role['slug'])->exists()) {
                DB::table('roles')->insert([
                    'name'       => $role['name'],
                    'slug'       => $role['slug'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereIn('slug', ['league_manager', 'league_steward'])->delete();
    }
};
