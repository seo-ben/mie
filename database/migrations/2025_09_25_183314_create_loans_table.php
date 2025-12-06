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
        Schema::create('loans', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            $table->string('loan_number', 30)->unique();
            $table->unsignedBigInteger('client_id');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('interest_rate', 12, 4);
            $table->integer('duration_months');
            $table->decimal('monthly_payment', 10, 2)->nullable();
            $table->decimal('total_amount_due', 15, 2)->nullable();
            $table->text('purpose')->nullable();
            $table->text('collateral_description')->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'disbursed', 'active', 'completed', 'defaulted'])->default('pending');
            $table->decimal('eligibility_score', 5, 2)->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'very_high'])->nullable();
            $table->timestamp('application_date')->useCurrent();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('disbursed_by')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->enum('disbursement_method', ['cash', 'bank_transfer', 'mobile_money'])->nullable();
            $table->string('disbursement_reference', 100)->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->decimal('outstanding_principal', 12, 2)->default(0.00);
            $table->decimal('outstanding_interest', 12, 2)->default(0.00);
            $table->decimal('total_paid', 15, 2)->default(0.00);
            $table->decimal('penalty_amount', 10, 2)->default(0.00);
            $table->integer('days_overdue')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('disbursed_by')->references('id')->on('users')->onDelete('set null');

            $table->index('loan_number');
            $table->index('client_id');
            $table->index('status');
            $table->index('application_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
