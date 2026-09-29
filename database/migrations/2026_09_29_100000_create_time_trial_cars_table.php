<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Time Trials car lookup, keyed on the numeric car ID from the hotlap source data. Some
// names appear under two IDs (different model years), so the ID is the only key and the
// name is never used for matching. acc_car_model is the console carModel ID in
// AccCarCatalog, null where the car has no known ACC model.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_trial_cars', function (Blueprint $table) {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('name', 100);
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('acc_car_model')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_trial_cars');
    }
};
