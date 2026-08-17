<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes pour accélérer les recherches du caissier.
     * 
     * Les recherches côté caissier utilisent:
     * - accounts: account_number, status, balance
     * - clients: first_name, last_name, client_number, phone
     * - loans: loan_number, status
     */
    public function up(): void
    {
        // Index composite sur la table accounts pour les recherches caissier
        Schema::table('accounts', function (Blueprint $table) {
            // Index pour la recherche par statut + numéro de compte (le plus fréquent)
            $table->index(['status', 'account_number'], 'idx_accounts_status_number');
            // Index pour la recherche retrait (statut + balance > 0)
            $table->index(['status', 'balance'], 'idx_accounts_status_balance');
        });

        // Index sur la table clients pour les recherches par nom/téléphone
        Schema::table('clients', function (Blueprint $table) {
            $table->index('phone', 'idx_clients_phone');
            $table->index('client_number', 'idx_clients_client_number');
            $table->index(['first_name', 'last_name'], 'idx_clients_name');
        });

        // Index sur la table loans pour les recherches caissier
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['status', 'loan_number'], 'idx_loans_status_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex('idx_accounts_status_number');
            $table->dropIndex('idx_accounts_status_balance');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_phone');
            $table->dropIndex('idx_clients_client_number');
            $table->dropIndex('idx_clients_name');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_status_number');
        });
    }
};
