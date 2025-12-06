<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Procédure: Calcul éligibilité prêt
        DB::unprepared("
            DROP PROCEDURE IF EXISTS CalculateLoanEligibility;
            CREATE PROCEDURE CalculateLoanEligibility(IN client_id_param BIGINT)
            BEGIN
                DECLARE savings_balance DECIMAL(15,2) DEFAULT 0;
                DECLARE tontine_regularity DECIMAL(5,2) DEFAULT 0;
                DECLARE eligibility_score DECIMAL(5,2) DEFAULT 0;
                
                -- Calculer le solde épargne
                SELECT COALESCE(SUM(a.balance), 0) INTO savings_balance
                FROM accounts a
                WHERE a.client_id = client_id_param 
                AND a.account_type = 'savings' 
                AND a.status = 'active';
                
                -- Calculer la régularité des paiements tontine
                SELECT AVG(CASE WHEN t.status = 'completed' THEN 100 ELSE 0 END) INTO tontine_regularity
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                WHERE a.client_id = client_id_param 
                AND a.account_type = 'tontine'
                AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH);
                
                -- Calculer le score d'éligibilité
                SET eligibility_score = (
                    (CASE WHEN savings_balance >= 50000 THEN 40 ELSE (savings_balance / 50000) * 40 END) +
                    (COALESCE(tontine_regularity, 0) * 0.6)
                );
                
                -- Mettre à jour le score du client
                UPDATE clients 
                SET credit_score = eligibility_score 
                WHERE id = client_id_param;
                
                SELECT eligibility_score as score, 
                       savings_balance,
                       COALESCE(tontine_regularity, 0) as regularity;
            END;
        ");

        // Procédure: Générer échéancier de prêt
        DB::unprepared("
            DROP PROCEDURE IF EXISTS GenerateLoanSchedule;
            CREATE PROCEDURE GenerateLoanSchedule(IN loan_id_param BIGINT)
            BEGIN
                DECLARE loan_amount DECIMAL(12,2);
                DECLARE interest_rate DECIMAL(5,4);
                DECLARE duration_months INT;
                DECLARE monthly_payment DECIMAL(10,2);
                DECLARE principal_payment DECIMAL(10,2);
                DECLARE interest_payment DECIMAL(10,2);
                DECLARE remaining_balance DECIMAL(12,2);
                DECLARE payment_date DATE;
                DECLARE i INT DEFAULT 1;
                
                SELECT approved_amount, interest_rate, duration_months, first_payment_date
                INTO loan_amount, interest_rate, duration_months, payment_date
                FROM loans
                WHERE id = loan_id_param AND status = 'approved';
                
                SET monthly_payment = loan_amount * (interest_rate/12) * POWER(1 + interest_rate/12, duration_months) / 
                                   (POWER(1 + interest_rate/12, duration_months) - 1);
                
                SET remaining_balance = loan_amount;
                
                DELETE FROM loan_payments WHERE loan_id = loan_id_param;
                
                WHILE i <= duration_months DO
                    SET interest_payment = remaining_balance * (interest_rate / 12);
                    SET principal_payment = monthly_payment - interest_payment;
                    SET remaining_balance = remaining_balance - principal_payment;
                    
                    INSERT INTO loan_payments (
                        loan_id, payment_number, due_date, expected_amount, 
                        principal_amount, interest_amount
                    ) VALUES (
                        loan_id_param, i, payment_date, monthly_payment, 
                        principal_payment, interest_payment
                    );
                    
                    SET payment_date = DATE_ADD(payment_date, INTERVAL 1 MONTH);
                    SET i = i + 1;
                END WHILE;
                
                UPDATE loans 
                SET monthly_payment = monthly_payment,
                    total_amount_due = monthly_payment * duration_months,
                    outstanding_principal = loan_amount,
                    maturity_date = DATE_ADD(first_payment_date, INTERVAL duration_months MONTH)
                WHERE id = loan_id_param;
            END;
        ");

        // Procédure: Historique complet client (corrigée)
        DB::unprepared("
            DROP PROCEDURE IF EXISTS GetClientCompleteHistory;
            CREATE PROCEDURE GetClientCompleteHistory(IN client_id_param BIGINT, IN limit_records INT)
            BEGIN
                DECLARE limit_value INT;
                SET limit_value = IFNULL(limit_records, 50);
                
                -- Historique des transactions
                SELECT 'TRANSACTIONS' as section_type;
                
                SELECT 
                    t.transaction_reference,
                    a.account_number,
                    t.transaction_type,
                    t.amount,
                    t.payment_method,
                    t.status,
                    t.transaction_date,
                    t.description
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                WHERE a.client_id = client_id_param
                ORDER BY t.transaction_date DESC
                LIMIT limit_value;
                
                -- Historique des prêts
                SELECT 'LOANS' as section_type;
                
                SELECT *
                FROM loans
                WHERE client_id = client_id_param
                ORDER BY application_date DESC;
                
                -- Résumé des comptes
                SELECT 'SUMMARY' as section_type;
                
                SELECT 
                    COUNT(DISTINCT a.id) as total_accounts,
                    IFNULL(SUM(a.balance), 0) as total_balance,
                    COUNT(DISTINCT CASE WHEN l.status IN ('active','disbursed') THEN l.id END) as active_loans
                FROM accounts a
                LEFT JOIN loans l ON a.client_id = l.client_id
                WHERE a.client_id = client_id_param;
            END;
        ");

        // Procédure: Calculer pénalités de retard
        DB::unprepared("
            DROP PROCEDURE IF EXISTS CalculateOverduePenalties;
            CREATE PROCEDURE CalculateOverduePenalties()
            BEGIN
                DECLARE done INT DEFAULT FALSE;
                DECLARE payment_id BIGINT;
                DECLARE loan_id_var BIGINT;
                DECLARE days_late INT;
                DECLARE expected_amount DECIMAL(10,2);
                DECLARE penalty_rate DECIMAL(5,4);
                
                DECLARE cur CURSOR FOR 
                    SELECT lp.id, lp.loan_id, DATEDIFF(CURDATE(), lp.due_date) as days_overdue, lp.expected_amount
                    FROM loan_payments lp
                    WHERE lp.status IN ('pending', 'partial') 
                    AND lp.due_date < CURDATE();
                
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
                
                SELECT CAST(parameter_value AS DECIMAL(5,4)) INTO penalty_rate
                FROM system_parameters 
                WHERE parameter_key = 'penalty_rate';
                
                OPEN cur;
                
                read_loop: LOOP
                    FETCH cur INTO payment_id, loan_id_var, days_late, expected_amount;
                    IF done THEN
                        LEAVE read_loop;
                    END IF;
                    
                    UPDATE loan_payments 
                    SET penalty_amount = expected_amount * penalty_rate * (days_late / 30),
                        days_overdue = days_late,
                        status = 'overdue'
                    WHERE id = payment_id;
                    
                    UPDATE loans
                    SET days_overdue = GREATEST(days_overdue, days_late),
                        penalty_amount = penalty_amount + (expected_amount * penalty_rate * (days_late / 30))
                    WHERE id = loan_id_var;
                    
                END LOOP;
                
                CLOSE cur;
            END;
        ");
    }

    public function down()
    {
        // Suppression des procédures dans l'ordre inverse
        DB::unprepared('DROP PROCEDURE IF EXISTS CalculateOverduePenalties');
        DB::unprepared('DROP PROCEDURE IF EXISTS GetClientCompleteHistory');
        DB::unprepared('DROP PROCEDURE IF EXISTS GenerateLoanSchedule');
        DB::unprepared('DROP PROCEDURE IF EXISTS CalculateLoanEligibility');
    }
};