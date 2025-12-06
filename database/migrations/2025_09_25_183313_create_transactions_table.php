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
        Schema::create('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->string('transaction_reference', 50)->unique();
            $table->unsignedBigInteger('account_id');
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer', 'fee', 'interest', 'penalty', 'payout', 'tontine_contribution', 'tontine_payout']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->default(0.00);
            $table->decimal('balance_after', 15, 2)->default(0.00);
            $table->enum('payment_method', ['cash', 'mobile_money', 'bank_transfer', 'system']);
            $table->decimal('withdrawal_fee', 15, 2)->nullable();
            $table->decimal('fee_amount', 15, 2)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('mobile_money_operator', 50)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->boolean('validation_required')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');

            $table->index('transaction_reference');
            $table->index(['account_id', 'transaction_date']);
            $table->index('transaction_type');
            $table->index('status');
            $table->index('payment_method');

            $table->unsignedBigInteger('related_account_id')->nullable()->after('account_id');
            $table->foreign('related_account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
