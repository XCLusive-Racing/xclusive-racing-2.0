<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// XCL Supporter memberships paid through Mollie: one row per user (their Mollie customer
// and subscription, and how long they're paid up for), plus every Mollie payment seen
// so a webhook delivered twice never extends a membership twice.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mollie_customer_id')->nullable();
            $table->string('mollie_mandate_id')->nullable();
            $table->string('mollie_subscription_id')->nullable();
            // pending (checkout not finished) / active / canceled (runs out at paid_until)
            $table->string('status', 20)->default('pending');
            $table->string('mode', 10)->nullable(); // Mollie 'test' or 'live'
            $table->timestamp('paid_until')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mollie_payment_id')->unique();
            $table->string('sequence_type', 20); // first / recurring
            $table->string('status', 20);
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3);
            $table->string('mode', 10)->nullable();
            // Set once the payment has extended the membership, so it never counts twice.
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
        Schema::dropIfExists('memberships');
    }
};
