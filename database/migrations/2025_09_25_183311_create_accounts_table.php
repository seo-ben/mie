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
        Schema::create('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->string('account_number', 30)->unique();
            $table->unsignedBigInteger('client_id');
            $table->enum('account_type', ['savings', 'tontine']);
            $table->enum('status', ['pending_activation', 'active', 'suspended', 'closed'])->default('pending_activation');
            $table->decimal('activation_fee', 10, 2)->default(0);
            $table->boolean('activation_fee_paid')->default(false);
            $table->enum('activation_payment_method', ['mobile_money', 'bank_transfer', 'cash', 'other'])->nullable();
            $table->string('activation_reference', 100)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamp('last_transaction_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('activated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index('account_number');
            $table->index(['client_id', 'account_type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
