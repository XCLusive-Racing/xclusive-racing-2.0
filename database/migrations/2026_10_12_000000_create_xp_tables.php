<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// XP and achievements, phase one (docs/xp-achievements/PLAN.md): every table is new, the
// only change to an existing table is two cache columns on users. The catalogue and the
// rank curve are seeded by the next migration.
return new class extends Migration
{
    public function up(): void
    {
        // The titles XP is earned in. A race's `game` maps onto one of these
        // (App\Xp\Titles): ACC on console and on PC are the same title, on two platforms.
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('games')->insert([
            ['key' => 'acc', 'name' => 'Assetto Corsa Competizione', 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'lmu', 'name' => 'Le Mans Ultimate', 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'iracing', 'name' => 'iRacing', 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->string('category', 60)->index();
            $table->string('family', 80)->nullable()->index(); // groups the tiers of the same thing
            $table->string('metric_key', 80)->index();        // App\Xp\MetricRegistry
            $table->string('game_scope', 20)->default('all')->index(); // 'all' or a games.key
            $table->decimal('threshold', 12, 2);
            // 'gte' for nearly everything; 'lte' where lower is better (a lap time).
            $table->string('comparison', 3)->default('gte');
            $table->unsignedInteger('xp');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_archived')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('achievement_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('xp_awarded'); // as awarded, never read back from the catalogue
            $table->string('game', 20)->nullable();  // the title the unlocking progress came from
            $table->unsignedBigInteger('race_id')->nullable()->index();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('awarded_at');
            $table->timestamp('seen_at')->nullable(); // null until the driver has watched the reveal
            $table->unique(['user_id', 'achievement_id']);
        });

        // The single source of truth for XP: a driver's total is the sum of their rows here.
        // Rows are only ever added.
        Schema::create('xp_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('xp');
            $table->enum('source', ['achievement', 'race', 'adjustment', 'backfill'])->index();
            $table->string('game', 20)->nullable()->index();
            $table->string('platform', 10)->nullable(); // console / pc
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            // What makes a grant unique per driver ("race:12:1", "achievement:40"), so
            // processing the same thing twice can't write it twice. Null for adjustments.
            $table->string('reference_key', 80)->nullable();
            $table->string('reason');
            $table->json('breakdown')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['user_id', 'reference_key']);
        });

        Schema::create('rank_levels', function (Blueprint $table) {
            $table->unsignedSmallInteger('rank')->primary();
            $table->unsignedInteger('xp_to_next')->nullable(); // null on the maximum rank
            $table->unsignedInteger('cumulative_xp');
        });

        // Last computed value per driver, metric and scope ('all' or a games.key), so
        // progress can be shown without recounting a driver's whole history.
        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('metric_key', 80);
            $table->string('scope', 20);
            $table->decimal('value', 14, 2);
            $table->timestamp('updated_at')->nullable();
            $table->unique(['user_id', 'metric_key', 'scope']);
        });

        // Each time a driver's rank went up, for the phase two reveal.
        Schema::create('xp_rank_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('from_rank');
            $table->unsignedSmallInteger('to_rank');
            $table->unsignedBigInteger('race_id')->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // What a race's stored raw results file says about one result row, read once when
        // the race is processed: no in-game penalty, every lap valid. Null = not known
        // (no file stored for that race).
        Schema::create('xp_race_facts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('race_result_id')->unique();
            $table->boolean('penalty_free')->nullable();
            $table->boolean('all_laps_valid')->nullable();
            $table->timestamps();
        });

        // Caches only, rebuilt from the ledger (App\Xp\XpAwarder::refreshTotals()).
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedSmallInteger('xp_rank')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['xp_total', 'xp_rank']);
        });

        foreach (['xp_race_facts', 'xp_rank_ups', 'user_metrics', 'rank_levels', 'xp_ledger', 'achievement_awards', 'achievements', 'games'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
