<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Driver classes (Pro / Pro-Am / Am …) — separate from the car classes of
// championship_classes: a league puts each entry in one by hand, whatever car it
// drives. Each class has its own standings next to the overall one.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championship_driver_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championship_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            // ACC entrylist driverCategory — the in-game number banner colour
            // (0 red, 1 grey, 2 white). Null = derive it from the XCL rating as usual.
            $table->unsignedTinyInteger('acc_category')->nullable();
            $table->unsignedInteger('max_entries')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('championship_registrations', function (Blueprint $table) {
            // Removing a class leaves its entries unassigned, never unregistered.
            $table->foreignId('driver_class_id')->nullable()->after('championship_class_id')
                ->constrained('championship_driver_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_class_id');
        });

        Schema::dropIfExists('championship_driver_classes');
    }
};
