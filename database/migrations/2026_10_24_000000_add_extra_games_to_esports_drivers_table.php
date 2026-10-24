<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A driver can race for more than one esports team: `game` stays their own team,
// `extra_games` lists any other team they also race for. José García (LMU),
// Kyle Williams (LMU) and Menno Peters (iRacing) also join the ACC team.
return new class extends Migration
{
    private array $accGuests = ['jose-garcia', 'kyle-williams', 'menno-peters'];

    public function up(): void
    {
        Schema::table('esports_drivers', function (Blueprint $table) {
            $table->json('extra_games')->nullable()->after('game');
        });

        DB::table('esports_drivers')->whereIn('slug', $this->accGuests)->where('game', '!=', 'acc')
            ->update(['extra_games' => json_encode(['acc']), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('esports_drivers', function (Blueprint $table) {
            $table->dropColumn('extra_games');
        });
    }
};
