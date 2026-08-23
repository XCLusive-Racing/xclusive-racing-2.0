<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users MODIFY sr_acc DECIMAL(4,2) NOT NULL DEFAULT 4.00');
        DB::statement('ALTER TABLE users MODIFY sr_lmu DECIMAL(4,2) NOT NULL DEFAULT 4.00');
        DB::statement('ALTER TABLE users MODIFY sr_iracing DECIMAL(4,2) NOT NULL DEFAULT 4.00');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY sr_acc DECIMAL(4,2) NOT NULL DEFAULT 5.00');
        DB::statement('ALTER TABLE users MODIFY sr_lmu DECIMAL(4,2) NOT NULL DEFAULT 5.00');
        DB::statement('ALTER TABLE users MODIFY sr_iracing DECIMAL(4,2) NOT NULL DEFAULT 5.00');
    }
};
