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
        Schema::create('transaction_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('transaction_id');
            $table->string('receipt_number', 50)->unique();
            $table->string('receipt_url', 255)->nullable();
            $table->enum('receipt_type', ['digital', 'physical', 'both'])->default('digital');
            $table->enum('sent_via', ['email', 'sms', 'app_notification', 'printed'])->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->index('receipt_number');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_receipts');
    }
};
