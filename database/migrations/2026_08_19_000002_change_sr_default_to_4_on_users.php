<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY sr_acc DECIMAL(4,2) NOT NULL DEFAULT 4.00');
            DB::statement('ALTER TABLE users MODIFY sr_lmu DECIMAL(4,2) NOT NULL DEFAULT 4.00');
            DB::statement('ALTER TABLE users MODIFY sr_iracing DECIMAL(4,2) NOT NULL DEFAULT 4.00');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('sr_acc', 4, 2)->default(4.00)->change();
            $table->decimal('sr_lmu', 4, 2)->default(4.00)->change();
            $table->decimal('sr_iracing', 4, 2)->default(4.00)->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY sr_acc DECIMAL(4,2) NOT NULL DEFAULT 5.00');
            DB::statement('ALTER TABLE users MODIFY sr_lmu DECIMAL(4,2) NOT NULL DEFAULT 5.00');
            DB::statement('ALTER TABLE users MODIFY sr_iracing DECIMAL(4,2) NOT NULL DEFAULT 5.00');
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('sr_acc', 4, 2)->default(5.00)->change();
            $table->decimal('sr_lmu', 4, 2)->default(5.00)->change();
            $table->decimal('sr_iracing', 4, 2)->default(5.00)->change();
        });
    }
};
