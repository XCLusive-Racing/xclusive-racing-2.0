<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Three membership plans (config/memberships.php: supporter / member / vip) instead of one.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('plan', 20)->default('supporter')->after('status');
        });
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->string('plan', 20)->default('supporter')->after('sequence_type');
        });
    }

    public function down(): void
    {
        Schema::table('memberships', fn (Blueprint $table) => $table->dropColumn('plan'));
        Schema::table('membership_payments', fn (Blueprint $table) => $table->dropColumn('plan'));
    }
};
