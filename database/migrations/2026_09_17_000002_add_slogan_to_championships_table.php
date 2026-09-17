<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            // Third, smallest line under the hero title — distinct from the existing
            // 'tagline' column (labelled "Subtitle" in the UI, e.g. "Season 1"). This one
            // is the UI's "Tagline" (e.g. "Where real drivers are born").
            $table->string('slogan', 150)->nullable()->after('tagline');
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropColumn('slogan');
        });
    }
};
