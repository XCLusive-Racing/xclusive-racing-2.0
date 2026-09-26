<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Grid-filler drivers: listed on the rating list and shown on event grids (see
// Race::fillerRegistrations()), but never real registrations. Created by `php artisan
// fillers:seed` from config/fillers.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_filler')->default(false)->index()->after('is_supporter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_filler']);
            $table->dropColumn('is_filler');
        });
    }
};
