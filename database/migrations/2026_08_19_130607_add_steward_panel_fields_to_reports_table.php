<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: `status` already exists on `reports` (string(20), default 'pending') and is
     * already restricted in-app to pending/investigating/resolved/dismissed via
     * Report::statuses() — it is not redefined here.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('steward_1_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('steward_1_verdict')->nullable()->after('steward_1_id');
            $table->string('steward_1_penalty', 10)->nullable()->after('steward_1_verdict');
            $table->decimal('steward_1_multiplier', 3, 1)->nullable()->after('steward_1_penalty');
            $table->text('steward_1_notes')->nullable()->after('steward_1_multiplier');
            $table->boolean('steward_1_red_flag')->default(false)->after('steward_1_notes');

            $table->foreignId('steward_2_id')->nullable()->after('steward_1_red_flag')->constrained('users')->nullOnDelete();
            $table->string('steward_2_verdict')->nullable()->after('steward_2_id');
            $table->string('steward_2_penalty', 10)->nullable()->after('steward_2_verdict');
            $table->decimal('steward_2_multiplier', 3, 1)->nullable()->after('steward_2_penalty');
            $table->text('steward_2_notes')->nullable()->after('steward_2_multiplier');
            $table->boolean('steward_2_red_flag')->default(false)->after('steward_2_notes');

            $table->string('final_penalty', 10)->nullable()->after('steward_2_red_flag');
            $table->decimal('final_multiplier', 3, 1)->nullable()->after('final_penalty');
            $table->enum('session_type', ['R', 'Q', 'P'])->nullable()->after('final_multiplier');

            $table->boolean('ready_to_process')->default(false)->after('session_type');
            $table->timestamp('processed_at')->nullable()->after('ready_to_process');
            $table->foreignId('processed_by')->nullable()->after('processed_at')->constrained('users')->nullOnDelete();

            $table->decimal('xcl_rating_deduction', 8, 4)->nullable()->after('processed_by');
            $table->decimal('xcl_rating_return', 8, 4)->nullable()->after('xcl_rating_deduction');
            $table->decimal('sr_deduction', 5, 2)->nullable()->after('xcl_rating_return');

            $table->text('dismissal_reason')->nullable()->after('sr_deduction');
            $table->boolean('ban_review_flagged')->default(false)->after('dismissal_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['steward_1_id']);
            $table->dropForeign(['steward_2_id']);
            $table->dropForeign(['processed_by']);

            $table->dropColumn([
                'steward_1_id', 'steward_1_verdict', 'steward_1_penalty', 'steward_1_multiplier', 'steward_1_notes', 'steward_1_red_flag',
                'steward_2_id', 'steward_2_verdict', 'steward_2_penalty', 'steward_2_multiplier', 'steward_2_notes', 'steward_2_red_flag',
                'final_penalty', 'final_multiplier', 'session_type',
                'ready_to_process', 'processed_at', 'processed_by',
                'xcl_rating_deduction', 'xcl_rating_return', 'sr_deduction',
                'dismissal_reason', 'ban_review_flagged',
            ]);
        });
    }
};
