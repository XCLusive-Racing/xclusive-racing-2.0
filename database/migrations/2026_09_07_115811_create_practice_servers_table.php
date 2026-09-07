<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No FTP credentials here — those already live on ftp_servers (host, port,
        // username, encrypted password, cfg_path). This table only holds the concerns
        // specific to running a dedicated practice box: capacity, its own restart
        // cadence, and the join password shown to drivers.
        Schema::create('practice_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ftp_server_id')->constrained('ftp_servers');
            $table->unsignedInteger('max_car_slots');
            $table->unsignedInteger('max_connections');
            $table->unsignedInteger('restart_cadence_minutes');
            $table->unsignedInteger('restart_offset_minutes')->default(0);
            $table->string('platform', 10); // pc | console
            $table->string('join_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_servers');
    }
};
