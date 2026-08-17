<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mettre à jour l'ENUM transaction_type pour supporter
     * les nouveaux types de transaction du caissier.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM(
            'deposit',
            'withdrawal',
            'transfer',
            'fee',
            'interest',
            'penalty',
            'payout',
            'tontine_contribution',
            'tontine_payout',
            'savings_deposit',
            'tontine_deposit',
            'loan_repayment',
            'loan_disbursement',
            'transfer_in',
            'transfer_out'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre l'ancien ENUM (attention: les données existantes avec les nouveaux types seront perdues)
        DB::statement("ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM(
            'deposit',
            'withdrawal',
            'transfer',
            'fee',
            'interest',
            'penalty',
            'payout',
            'tontine_contribution',
            'tontine_payout'
        ) NOT NULL");
    }
};
