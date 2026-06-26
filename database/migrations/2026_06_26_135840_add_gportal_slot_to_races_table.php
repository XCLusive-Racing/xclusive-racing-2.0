<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('ftp_server_id')->nullable()->after('event_format_id')
                  ->constrained('ftp_servers')->nullOnDelete();
            $table->timestamp('slot_time')->nullable()->after('ftp_server_id');
            $table->timestamp('config_pushed_at')->nullable()->after('slot_time');
            $table->string('config_push_status', 20)->nullable()->after('config_pushed_at');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropForeign(['ftp_server_id']);
            $table->dropColumn(['ftp_server_id', 'slot_time', 'config_pushed_at', 'config_push_status']);
        });
    }
};
