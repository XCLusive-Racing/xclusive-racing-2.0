<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// "Mandatory Refuelling" as its own on/off option (2026-10-26, a driver noticed the
// Daily pushes isMandatoryPitstopRefuellingRequired=false). It used to be tied to the
// 25s fixed stop time; now each is set on its own. The backfill copies the old
// coupling, so every existing format/round/championship pushes the same config as
// before until an admin flips the new option.
return new class extends Migration
{
    private const TABLES = ['races', 'event_formats', 'session_formats'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->boolean('mandatory_refuelling')->default(false)->after('pitstop_count');
            });
        }

        DB::table('races')->whereNotNull('min_stop_secs')->update(['mandatory_refuelling' => true]);
        DB::table('event_formats')->whereNotNull('min_stop_secs')->update(['mandatory_refuelling' => true]);
        DB::table('session_formats')->where('fixed_stop_time', true)->update(['mandatory_refuelling' => true]);

        DB::table('championships')->select('id', 'settings')->orderBy('id')->each(function ($championship) {
            $settings = json_decode($championship->settings ?? '', true);
            if (! is_array($settings) || ! isset($settings['sessions']) || isset($settings['sessions']['mandatory_refuelling'])) {
                return;
            }

            $settings['sessions']['mandatory_refuelling'] = (bool) ($settings['sessions']['fixed_stop_time'] ?? false);
            DB::table('championships')->where('id', $championship->id)->update(['settings' => json_encode($settings)]);
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('mandatory_refuelling');
            });
        }
    }
};
