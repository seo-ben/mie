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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->unsignedBigInteger('loan_id');
            $table->integer('payment_number');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->decimal('expected_amount',10,2);
            $table->decimal('principal_amount',10,2);
            $table->decimal('interest_amount',10,2);
            $table->decimal('penalty_amount',8,2)->default(0.00);
            $table->decimal('paid_amount',10,2)->default(0.00);
            $table->enum('payment_method',['cash','mobile_money','bank_transfer','auto_debit'])->nullable();
            $table->string('payment_reference',100)->nullable();
            $table->enum('status',['pending','paid','partial','overdue','cancelled'])->default('pending');
            $table->integer('days_overdue')->default(0);
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('days_overdue');
            $table->index(['loan_id', 'payment_number']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
