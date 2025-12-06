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
        Schema::create('tontine_cycles', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('tontine_account_id');
            $table->integer('cycle_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('target_amount', 12, 2);
            $table->decimal('collected_amount', 12, 2)->default(0.00);
            $table->decimal('payout_amount', 12, 2)->default(0.00);
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->date('payout_date')->nullable();
            $table->timestamps();

            $table->foreign('tontine_account_id')->references('id')->on('tontine_accounts')->onDelete('cascade');
            $table->unique(['tontine_account_id', 'cycle_number']);
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tontine_cycles');
    }
};
