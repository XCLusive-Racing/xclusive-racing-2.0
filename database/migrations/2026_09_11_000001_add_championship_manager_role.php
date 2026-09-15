<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (!DB::table('roles')->where('slug', 'championship_manager')->exists()) {
            DB::table('roles')->insert([
                'name'       => 'Championship Manager',
                'slug'       => 'championship_manager',
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'championship_manager')->delete();
    }
};
