<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date');
            $table->string('payment_type')->default('salary'); // salary, bonus, advance, expense_reimbursement
            $table->string('payment_method')->default('cash');
            $table->foreignId('cashier_session_id')->nullable()->constrained('cashier_sessions');
            $table->foreignId('processed_by')->constrained('users');
            $table->string('status')->default('paid');
            $table->string('transaction_reference')->unique()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_payments');
    }
};
