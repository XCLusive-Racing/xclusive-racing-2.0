<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('races')->where('car_class', 'M2')->update(['car_class' => 'TCX']);

        DB::table('race_classes')->where('car_class', 'M2')->update(['car_class' => 'TCX']);
        DB::table('race_classes')->where('name', 'M2')->update(['name' => 'TCX']);
    }

    public function down(): void
    {
        DB::table('races')->where('car_class', 'TCX')->update(['car_class' => 'M2']);

        DB::table('race_classes')->where('car_class', 'TCX')->update(['car_class' => 'M2']);
        DB::table('race_classes')->where('name', 'TCX')->update(['name' => 'M2']);
    }
};