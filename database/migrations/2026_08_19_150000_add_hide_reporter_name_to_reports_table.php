<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets the reporting driver opt to stay anonymous to the reported driver.
     * Stewards always see the real reporter on the admin report views regardless
     * of this flag — it only affects the driver-facing "Reports Against Me" list.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('hide_reporter_name')->default(false)->after('reporter_driver_name');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('hide_reporter_name');
        });
    }
};
