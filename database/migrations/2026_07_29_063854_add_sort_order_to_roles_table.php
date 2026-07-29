<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('slug');
        });

        $order = [
            'owner'         => 1,
            'admin'         => 2,
            'moderator'     => 3,
            'event_manager' => 4,
            'steward'       => 5,
            'broadcaster'   => 6,
            'driver'        => 7,
        ];

        foreach ($order as $slug => $position) {
            DB::table('roles')->where('slug', $slug)->update(['sort_order' => $position]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
