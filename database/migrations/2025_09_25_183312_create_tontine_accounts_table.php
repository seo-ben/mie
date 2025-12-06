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
        Schema::create('tontine_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('account_id');
            $table->decimal('tontine_amount', 8, 2);
            $table->integer('cycle_duration_months')->default(12);
            $table->enum('payment_frequency', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->decimal('expected_monthly_payment', 8, 2);
            $table->decimal('total_expected', 12, 2);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->integer('payments_made')->default(0);
            $table->integer('current_cycle')->default(1);
            $table->date('cycle_start_date')->nullable();
            $table->date('cycle_end_date')->nullable();
            $table->decimal('payout_amount', 12, 2)->default(0.00);
            $table->date('payout_date')->nullable();
            $table->decimal('penalty_rate', 5, 4)->default(0.0000);
            $table->decimal('total_penalties', 10, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index('account_id');
            $table->index('current_cycle');
            $table->index('tontine_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tontine_accounts');
    }
};
