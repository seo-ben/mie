<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vue pour le résumé des comptes clients
        DB::statement("
            CREATE OR REPLACE VIEW vw_client_account_summary AS
            SELECT
                c.id as client_id,
                c.client_number,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                COUNT(DISTINCT a.id) as total_accounts,
                SUM(CASE WHEN a.account_type = 'savings' THEN a.balance ELSE 0 END) as total_savings,
                SUM(CASE WHEN a.account_type = 'tontine' THEN a.balance ELSE 0 END) as total_tontine,
                (SELECT COUNT(*) FROM loans l WHERE l.client_id = c.id AND l.status = 'active') as active_loans,
                (SELECT SUM(outstanding_principal + outstanding_interest)
                 FROM loans l WHERE l.client_id = c.id AND l.status = 'active') as total_loan_outstanding
            FROM clients c
            LEFT JOIN accounts a ON c.id = a.client_id
            GROUP BY c.id, c.client_number, c.first_name, c.last_name
        ");

        // Vue pour les transactions journalières
        DB::statement("
            CREATE OR REPLACE VIEW vw_daily_transactions AS
            SELECT
                DATE(t.transaction_date) as trans_date,
                a.account_type,
                t.transaction_type,
                COUNT(*) as transaction_count,
                SUM(t.amount) as total_amount
            FROM transactions t
            JOIN accounts a ON t.account_id = a.id
            WHERE t.status = 'completed'
            GROUP BY DATE(t.transaction_date), a.account_type, t.transaction_type
        ");

        // Trigger pour mettre à jour le solde du compte après une transaction
        DB::unprepared("
            CREATE TRIGGER trg_after_transaction_complete
            AFTER UPDATE ON transactions
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
                    UPDATE accounts
                    SET balance = balance +
                        CASE
                            WHEN NEW.transaction_type IN ('deposit', 'transfer_in') THEN NEW.amount
                            WHEN NEW.transaction_type IN ('withdrawal', 'transfer_out') THEN -NEW.amount
                            ELSE 0
                        END,
                        last_transaction_at = NEW.transaction_date
                    WHERE id = NEW.account_id;
                END IF;
            END
        ");

        // Trigger pour le calcul automatique des pénalités de prêt
        DB::unprepared("
            CREATE TRIGGER trg_calculate_loan_penalties
            BEFORE UPDATE ON loan_payments
            FOR EACH ROW
            BEGIN
                IF NEW.paid_date IS NOT NULL AND NEW.paid_date > NEW.due_date THEN
                    SET NEW.days_overdue = DATEDIFF(NEW.paid_date, NEW.due_date);
                    SET NEW.penalty_amount = (NEW.expected_amount * 0.01 * NEW.days_overdue);
                END IF;
            END
        ");

        // Trigger pour la mise à jour automatique du statut du prêt
        DB::unprepared("
            CREATE TRIGGER trg_update_loan_status
            AFTER INSERT ON loan_payments
            FOR EACH ROW
            BEGIN
                DECLARE total_paid DECIMAL(15,2);
                DECLARE total_due DECIMAL(15,2);

                -- ✅ Calcul du total payé pour ce prêt
                SELECT SUM(paid_amount)
                INTO total_paid
                FROM loan_payments
                WHERE loan_id = NEW.loan_id;

                -- ✅ Récupération du montant total dû du prêt
                SELECT total_amount_due
                INTO total_due
                FROM loans
                WHERE id = NEW.loan_id;

                -- ✅ Si tout est payé, on marque le prêt comme terminé
                IF total_paid >= total_due THEN
                    UPDATE loans
                    SET status = 'completed'
                    WHERE id = NEW.loan_id;
                END IF;
            END
        ");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Suppression des vues
        DB::statement("DROP VIEW IF EXISTS vw_client_account_summary");
        DB::statement("DROP VIEW IF EXISTS vw_daily_transactions");

        // Suppression des triggers
        DB::unprepared("DROP TRIGGER IF EXISTS trg_after_transaction_complete");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_calculate_loan_penalties");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_update_loan_status");
    }
};
