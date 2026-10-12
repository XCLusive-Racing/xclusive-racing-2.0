<?php

use App\Xp\CatalogueImporter;
use App\Xp\RankCurve;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Seeds the achievement catalogue and the rank curve from database/data/xp (the CSV
// exports of docs/xp-achievements/xcl-xp-achievements.xlsx), then fits the curve so the
// title agnostic catalogue lands exactly on rank 150 (the two Nordschleife families the
// sheet scoped "all" are ACC only, which takes them out of that pool).
return new class extends Migration
{
    public function up(): void
    {
        $importer = app(CatalogueImporter::class);
        $importer->importRankCurve(database_path('data/xp/rank-curve.csv'));
        $importer->importAchievements(database_path('data/xp/achievements.csv'));

        app(RankCurve::class)->refit();
    }

    public function down(): void
    {
        DB::table('achievement_awards')->delete();
        DB::table('achievements')->delete();
        DB::table('rank_levels')->delete();
    }
};
