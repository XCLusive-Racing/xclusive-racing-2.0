<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A weekly Time Trial event: one track and car class on one server for a set window.
// Signups become the forced entry list pushed before every server restart; the hourly
// practice results are collected into time_trial_event_laps, and once the window closes
// the event is finalized (results, rating points, laps merged into the all-time records).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_trial_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('track', 64);
            $table->string('car_class', 16)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->foreignId('ftp_server_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_published')->default(false);
            $table->timestamp('last_pushed_for')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->text('last_push_error')->nullable();
            $table->unsignedInteger('last_entry_count')->nullable();
            $table->timestamp('results_checked_at')->nullable();
            $table->text('results_error')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'ends_at'], 'tt_events_window_index');
        });

        Schema::create('time_trial_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_trial_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['time_trial_event_id', 'user_id'], 'tt_registrations_event_user_unique');
        });

        // One row per driver, car and result file: that session's best valid lap.
        Schema::create('time_trial_event_laps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_trial_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform_identifier', 64);
            $table->enum('platform', ['xbox', 'playstation', 'pc']);
            $table->string('driver_name');
            $table->unsignedSmallInteger('car_id');
            $table->string('car_class', 16);
            $table->unsignedInteger('lap_time_ms');
            $table->unsignedInteger('sector1_ms')->nullable();
            $table->unsignedInteger('sector2_ms')->nullable();
            $table->unsignedInteger('sector3_ms')->nullable();
            $table->string('result_file', 128);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['time_trial_event_id', 'result_file', 'platform_identifier', 'car_id'], 'tt_event_laps_file_driver_car_unique');
            $table->index(['time_trial_event_id', 'lap_time_ms'], 'tt_event_laps_event_lap_index');
        });

        // Every result file looked at, so a file is downloaded and parsed only once.
        Schema::create('time_trial_result_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_trial_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ftp_server_id')->constrained()->cascadeOnDelete();
            $table->string('filename', 128);
            $table->unsignedInteger('lap_count')->default(0);
            $table->timestamps();

            $table->unique(['ftp_server_id', 'filename'], 'tt_result_files_server_file_unique');
        });

        // The final classification, written once when the event is finalized.
        Schema::create('time_trial_event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_trial_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform_identifier', 64);
            $table->string('driver_name');
            $table->unsignedInteger('position');
            $table->unsignedSmallInteger('car_id');
            $table->unsignedInteger('lap_time_ms');
            $table->integer('rating_change')->default(0);
            $table->integer('rating_before')->nullable();
            $table->timestamps();

            $table->unique(['time_trial_event_id', 'platform_identifier'], 'tt_event_results_event_driver_unique');
        });

        // All-time laps that came from a weekly event (null for the historical import).
        Schema::table('time_trial_laps', function (Blueprint $table) {
            $table->foreignId('time_trial_event_id')->nullable()->after('source')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_trial_laps', function (Blueprint $table) {
            $table->dropConstrainedForeignId('time_trial_event_id');
        });
        Schema::dropIfExists('time_trial_event_results');
        Schema::dropIfExists('time_trial_result_files');
        Schema::dropIfExists('time_trial_event_laps');
        Schema::dropIfExists('time_trial_registrations');
        Schema::dropIfExists('time_trial_events');
    }
};
