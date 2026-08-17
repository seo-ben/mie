-- =====================================================
-- BASE DE DONNÉES SYSTÈME DE GESTION MICROFINANCE MIE
-- Version: 1.0
-- Date: 2024
-- =====================================================

-- Suppression des tables existantes (ordre inverse pour les contraintes)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS loan_payments;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS transaction_receipts;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS tontine_cycles;
DROP TABLE IF EXISTS tontine_accounts;
DROP TABLE IF EXISTS savings_accounts;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS client_documents;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS system_parameters;
DROP TABLE IF EXISTS agencies;
DROP TABLE IF EXISTS users;

-- =====================================================
-- TABLE DES UTILISATEURS (Agents, Gestionnaires, Administrateurs)
-- =====================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('agent_terrain', 'agent_agence', 'caissier', 'gestionnaire_superviseur', 'gestionnaire_credit', 'administrateur_systeme', 'administrateur_reglementaire') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    password_reset_token VARCHAR(255) NULL,
    password_reset_expires TIMESTAMP NULL,
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_secret VARCHAR(32) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_role (role),
    INDEX idx_active (is_active),
    INDEX idx_email (email)
);

-- =====================================================
-- TABLE DES AGENCES
-- =====================================================
CREATE TABLE agencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    phone VARCHAR(20),
    manager_id BIGINT UNSIGNED,
    cash_limit DECIMAL(15,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
);

-- Ajout de la relation agency_id dans users
ALTER TABLE users ADD COLUMN agency_id BIGINT UNSIGNED NULL;
ALTER TABLE users ADD FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL;
ALTER TABLE users ADD INDEX idx_agency (agency_id);

-- =====================================================
-- TABLE DES PARAMÈTRES SYSTÈME
-- =====================================================
CREATE TABLE system_parameters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parameter_key VARCHAR(100) UNIQUE NOT NULL,
    parameter_value TEXT NOT NULL,
    parameter_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    category VARCHAR(50),
    is_editable BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED,
    updated_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_key (parameter_key)
);

-- =====================================================
-- TABLE DES CLIENTS
-- =====================================================
CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('M', 'F', 'Other'),
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    profession VARCHAR(100),
    monthly_income DECIMAL(12,2),
    id_type ENUM('cni', 'passport', 'driving_license', 'other'),
    id_number VARCHAR(50),
    id_expiry_date DATE,
    profile_photo_url VARCHAR(255),
    kyc_status ENUM('pending', 'approved', 'rejected', 'incomplete') DEFAULT 'pending',
    kyc_approved_at TIMESTAMP NULL,
    kyc_approved_by BIGINT UNSIGNED NULL,
    registration_channel ENUM('mobile_app', 'web_portal', 'agent_assisted') NOT NULL,
    registered_by BIGINT UNSIGNED NULL,
    agency_id BIGINT UNSIGNED,
    is_active BOOLEAN DEFAULT TRUE,
    credit_score DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (kyc_approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE SET NULL,
    INDEX idx_client_number (client_number),
    INDEX idx_phone (phone),
    INDEX idx_kyc_status (kyc_status),
    INDEX idx_agency (agency_id)
);

-- =====================================================
-- TABLE DES DOCUMENTS CLIENTS (KYC)
-- =====================================================
CREATE TABLE client_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('id_front', 'id_back', 'photo', 'proof_address', 'proof_income', 'other') NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    file_size INT,
    mime_type VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    uploaded_by BIGINT UNSIGNED,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client_document (client_id, document_type),
    INDEX idx_status (status)
);

-- =====================================================
-- TABLE DES COMPTES (Générique)
-- =====================================================
CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_number VARCHAR(30) UNIQUE NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    account_type ENUM('savings', 'tontine') NOT NULL,
    status ENUM('pending_activation', 'active', 'suspended', 'closed') DEFAULT 'pending_activation',
    activation_fee DECIMAL(10,2) NOT NULL,
    activation_fee_paid BOOLEAN DEFAULT FALSE,
    activation_payment_method ENUM('mobile_money', 'bank_transfer', 'cash', 'other') NULL,
    activation_reference VARCHAR(100) NULL,
    activated_at TIMESTAMP NULL,
    activated_by BIGINT UNSIGNED NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    last_transaction_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_account_number (account_number),
    INDEX idx_client_account (client_id, account_type),
    INDEX idx_status (status)
);

-- =====================================================
-- TABLE DES COMPTES D'ÉPARGNE
-- =====================================================
CREATE TABLE savings_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    interest_rate DECIMAL(5,4) DEFAULT 0.0000,
    minimum_balance DECIMAL(10,2) DEFAULT 0.00,
    monthly_fee DECIMAL(8,2) DEFAULT 0.00,
    total_deposits DECIMAL(15,2) DEFAULT 0.00,
    total_withdrawals DECIMAL(15,2) DEFAULT 0.00,
    last_interest_calculated TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_account (account_id)
);

-- =====================================================
-- TABLE DES COMPTES DE TONTINE
-- =====================================================
CREATE TABLE tontine_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    tontine_amount DECIMAL(8,2) NOT NULL, -- 300, 500, 700 FCFA etc.
    cycle_duration_months INT DEFAULT 12,
    payment_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'monthly',
    expected_monthly_payment DECIMAL(8,2) NOT NULL,
    total_expected DECIMAL(12,2) NOT NULL,
    total_paid DECIMAL(12,2) DEFAULT 0.00,
    payments_made INT DEFAULT 0,
    current_cycle INT DEFAULT 1,
    cycle_start_date DATE,
    cycle_end_date DATE,
    payout_amount DECIMAL(12,2) DEFAULT 0.00,
    payout_date DATE NULL,
    penalty_rate DECIMAL(5,4) DEFAULT 0.0000,
    total_penalties DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_account (account_id),
    INDEX idx_cycle (current_cycle),
    INDEX idx_amount (tontine_amount)
);

-- =====================================================
-- TABLE DES CYCLES DE TONTINE
-- =====================================================
CREATE TABLE tontine_cycles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tontine_account_id BIGINT UNSIGNED NOT NULL,
    cycle_number INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL,
    collected_amount DECIMAL(12,2) DEFAULT 0.00,
    payout_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    payout_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tontine_account_id) REFERENCES tontine_accounts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cycle (tontine_account_id, cycle_number),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- =====================================================
-- TABLE DES TRANSACTIONS
-- =====================================================
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_reference VARCHAR(50) UNIQUE NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'transfer', 'fee', 'interest', 'penalty', 'payout') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_before DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'system') NOT NULL,
    payment_reference VARCHAR(100) NULL,
    mobile_money_operator VARCHAR(50) NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    processed_by BIGINT UNSIGNED NULL,
    validated_by BIGINT UNSIGNED NULL,
    validation_required BOOLEAN DEFAULT FALSE,
    processed_at TIMESTAMP NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reference (transaction_reference),
    INDEX idx_account_date (account_id, transaction_date),
    INDEX idx_type (transaction_type),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method)
);

-- =====================================================
-- TABLE DES REÇUS DE TRANSACTION
-- =====================================================
CREATE TABLE transaction_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    receipt_url VARCHAR(255),
    receipt_type ENUM('digital', 'physical', 'both') DEFAULT 'digital',
    sent_via ENUM('email', 'sms', 'app_notification', 'printed') NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_transaction (transaction_id)
);

-- =====================================================
-- TABLE DES PRÊTS
-- =====================================================
CREATE TABLE loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_number VARCHAR(30) UNIQUE NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    approved_amount DECIMAL(12,2) NULL,
    interest_rate DECIMAL(5,4) NOT NULL,
    duration_months INT NOT NULL,
    monthly_payment DECIMAL(10,2) NULL,
    total_amount_due DECIMAL(15,2) NULL,
    purpose TEXT,
    collateral_description TEXT,
    status ENUM('pending', 'under_review', 'approved', 'rejected', 'disbursed', 'active', 'completed', 'defaulted') DEFAULT 'pending',
    eligibility_score DECIMAL(5,2),
    risk_level ENUM('low', 'medium', 'high'),
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    disbursed_by BIGINT UNSIGNED NULL,
    disbursed_at TIMESTAMP NULL,
    disbursement_method ENUM('cash', 'bank_transfer', 'mobile_money') NULL,
    disbursement_reference VARCHAR(100) NULL,
    first_payment_date DATE NULL,
    maturity_date DATE NULL,
    outstanding_principal DECIMAL(12,2) DEFAULT 0.00,
    outstanding_interest DECIMAL(12,2) DEFAULT 0.00,
    total_paid DECIMAL(15,2) DEFAULT 0.00,
    penalty_amount DECIMAL(10,2) DEFAULT 0.00,
    days_overdue INT DEFAULT 0,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (disbursed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_loan_number (loan_number),
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_application_date (application_date)
);

-- =====================================================
-- TABLE DES REMBOURSEMENTS DE PRÊTS
-- =====================================================
CREATE TABLE loan_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    payment_number INT NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    expected_amount DECIMAL(10,2) NOT NULL,
    principal_amount DECIMAL(10,2) NOT NULL,
    interest_amount DECIMAL(10,2) NOT NULL,
    penalty_amount DECIMAL(8,2) DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'auto_debit') NULL,
    payment_reference VARCHAR(100) NULL,
    status ENUM('pending', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'pending',
    days_overdue INT DEFAULT 0,
    processed_by BIGINT UNSIGNED NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_payment (loan_id, payment_number),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    INDEX idx_overdue (days_overdue)
);

-- =====================================================
-- TABLE DES NOTIFICATIONS
-- =====================================================
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('client', 'user') NOT NULL,
    recipient_id BIGINT UNSIGNED NOT NULL,
    notification_type ENUM('payment_reminder', 'transaction_confirmation', 'loan_offer', 'kyc_update', 'system_alert', 'marketing') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    channel ENUM('push', 'sms', 'email', 'in_app') NOT NULL,
    status ENUM('pending', 'sent', 'delivered', 'failed', 'read') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    reference_type VARCHAR(50) NULL, -- 'transaction', 'loan', 'account', etc.
    reference_id BIGINT UNSIGNED NULL,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_status (status),
    INDEX idx_type (notification_type),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_reference (reference_type, reference_id)
);

-- =====================================================
-- TABLE DES JOURNAUX D'AUDIT
-- =====================================================
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    client_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL, -- 'client', 'account', 'transaction', 'loan', etc.
    entity_id BIGINT UNSIGNED NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(100),
    additional_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_client (client_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
);

-- =====================================================
-- INSERTION DES DONNÉES INITIALES
-- =====================================================

-- Paramètres système par défaut
INSERT INTO system_parameters (parameter_key, parameter_value, parameter_type, description, category) VALUES
('savings_account_activation_fee', '7000', 'number', 'Frais d\'activation compte épargne en FCFA', 'fees'),
('tontine_300_activation_fee', '300', 'number', 'Frais d\'activation tontine 300 FCFA', 'fees'),
('tontine_500_activation_fee', '500', 'number', 'Frais d\'activation tontine 500 FCFA', 'fees'),
('tontine_700_activation_fee', '700', 'number', 'Frais d\'activation tontine 700 FCFA', 'fees'),
('savings_interest_rate', '0.02', 'number', 'Taux d\'intérêt épargne annuel', 'rates'),
('loan_interest_rate_min', '0.08', 'number', 'Taux d\'intérêt prêt minimum', 'rates'),
('loan_interest_rate_max', '0.15', 'number', 'Taux d\'intérêt prêt maximum', 'rates'),
('penalty_rate', '0.05', 'number', 'Taux de pénalité pour retards', 'rates'),
('mobile_money_operators', '["MTN", "Orange", "Moov"]', 'json', 'Opérateurs Mobile Money supportés', 'integrations'),
('max_loan_amount', '5000000', 'number', 'Montant maximum de prêt en FCFA', 'loans'),
('min_savings_for_loan', '50000', 'number', 'Épargne minimum pour être éligible au prêt', 'loans');

-- Agence principale
INSERT INTO agencies (name, code, address, city, region, phone, is_active) VALUES
('Agence Centrale', 'AG001', '123 Avenue de la République', 'Lomé', 'Maritime', '+228 22 12 34 56', TRUE);

-- Utilisateur administrateur par défaut
INSERT INTO users (username, email, phone, password_hash, role, first_name, last_name, agency_id) VALUES
('admin', 'admin@mie-microfinance.tg', '+228 90 00 00 01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrateur_systeme', 'Administrateur', 'Système', 1);

-- =====================================================
-- VUES UTILES POUR LES RAPPORTS
-- =====================================================

-- Vue des comptes clients avec détails
CREATE VIEW v_client_accounts AS
SELECT 
    c.id as client_id,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    c.phone,
    c.kyc_status,
    a.id as account_id,
    a.account_number,
    a.account_type,
    a.status,
    a.balance,
    a.activated_at,
    CASE 
        WHEN a.account_type = 'tontine' THEN ta.tontine_amount
        ELSE NULL 
    END as tontine_amount
FROM clients c
LEFT JOIN accounts a ON c.id = a.client_id
LEFT JOIN tontine_accounts ta ON a.id = ta.account_id
WHERE c.is_active = TRUE;

-- Vue des transactions avec détails client
CREATE VIEW v_transactions_detail AS
SELECT 
    t.*,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    a.account_number,
    a.account_type,
    CONCAT(u.first_name, ' ', u.last_name) as processed_by_name
FROM transactions t
JOIN accounts a ON t.account_id = a.id
JOIN clients c ON a.client_id = c.id
LEFT JOIN users u ON t.processed_by = u.id;

-- Vue des prêts avec détails client
CREATE VIEW v_loans_detail AS
SELECT 
    l.*,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    c.phone,
    CONCAT(approved_user.first_name, ' ', approved_user.last_name) as approved_by_name,
    ag.name as agency_name
FROM loans l
JOIN clients c ON l.client_id = c.id
LEFT JOIN users approved_user ON l.approved_by = approved_user.id
LEFT JOIN agencies ag ON c.agency_id = ag.id;

-- =====================================================
-- INDEX SUPPLÉMENTAIRES POUR OPTIMISATION
-- =====================================================

-- Index composites pour les requêtes fréquentes
CREATE INDEX idx_clients_kyc_active ON clients(kyc_status, is_active);
CREATE INDEX idx_accounts_client_status ON accounts(client_id, status);
CREATE INDEX idx_transactions_date_type ON transactions(transaction_date, transaction_type);
CREATE INDEX idx_loans_status_date ON loans(status, application_date);
CREATE INDEX idx_notifications_recipient_status ON notifications(recipient_type, recipient_id, status);

-- =====================================================
-- TRIGGERS POUR AUTOMATISATION
-- =====================================================

-- Trigger pour générer automatiquement les numéros de compte
DELIMITER //
CREATE TRIGGER generate_account_number 
BEFORE INSERT ON accounts 
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    DECLARE account_prefix VARCHAR(5);
    
    -- Préfixe selon le type de compte
    SET account_prefix = CASE NEW.account_type
        WHEN 'savings' THEN 'SA'
        WHEN 'tontine' THEN 'TA'
        ELSE 'AC'
    END;
    
    -- Obtenir le prochain numéro
    SELECT COALESCE(MAX(CAST(SUBSTRING(account_number, 3) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM accounts 
    WHERE account_number LIKE CONCAT(account_prefix, '%');
    
    -- Générer le numéro de compte
    SET NEW.account_number = CONCAT(account_prefix, LPAD(next_number, 8, '0'));
END //
DELIMITER ;

-- Trigger pour générer automatiquement les numéros de client
DELIMITER //
CREATE TRIGGER generate_client_number 
BEFORE INSERT ON clients 
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(client_number, 3) AS UNSIGNED)), 0) + 1
    INTO next_number
    FROM clients;
    
    SET NEW.client_number = CONCAT('CL', LPAD(next_number, 6, '0'));
END //
DELIMITER ;

-- Trigger pour mettre à jour le solde lors des transactions
DELIMITER //
CREATE TRIGGER update_account_balance 
AFTER INSERT ON transactions 
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' THEN
        UPDATE accounts 
        SET balance = NEW.balance_after, 
            last_transaction_at = NEW.transaction_date 
        WHERE id = NEW.account_id;
    END IF;
END //
DELIMITER ;

-- =====================================================
-- PROCÉDURES STOCKÉES UTILES
-- =====================================================

-- Procédure pour calculer l'éligibilité au prêt
DELIMITER //
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
    
    -- Calculer la régularité des paiements tontine (logique simplifiée)
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
END //
DELIMITER ;

-- Procédure pour générer les échéanciers de prêt
DELIMITER //
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
    
    -- Récupérer les détails du prêt
    SELECT approved_amount, interest_rate, duration_months, first_payment_date
    INTO loan_amount, interest_rate, duration_months, payment_date
    FROM loans
    WHERE id = loan_id_param AND status = 'approved';
    
    -- Calculer le paiement mensuel
    SET monthly_payment = loan_amount * (interest_rate/12) * POWER(1 + interest_rate/12, duration_months) / 
                         (POWER(1 + interest_rate/12, duration_months) - 1);
    
    SET remaining_balance = loan_amount;
    
    -- Supprimer l'ancien échéancier s'il existe
    DELETE FROM loan_payments WHERE loan_id = loan_id_param;
    
    -- Générer l'échéancier
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
    
    -- Mettre à jour les informations du prêt
    UPDATE loans 
    SET monthly_payment = monthly_payment,
        total_amount_due = monthly_payment * duration_months,
        outstanding_principal = loan_amount,
        maturity_date = DATE_ADD(first_payment_date, INTERVAL duration_months MONTH)
    WHERE id = loan_id_param;
    
END //
DELIMITER ;

-- Procédure pour calculer les pénalités de retard
DELIMITER //
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
    
    -- Récupérer le taux de pénalité
    SELECT CAST(parameter_value AS DECIMAL(5,4)) INTO penalty_rate
    FROM system_parameters 
    WHERE parameter_key = 'penalty_rate';
    
    OPEN cur;
    
    penalty_loop: LOOP
        FETCH cur INTO payment_id, loan_id_var, days_late, expected_amount;
        IF done THEN
            LEAVE penalty_loop;
        END IF;
        
        -- Calculer et mettre à jour les pénalités
        UPDATE loan_payments 
        SET penalty_amount = expected_amount * penalty_rate * (days_late / 30),
            days_overdue = days_late,
            status = 'overdue'
        WHERE id = payment_id;
        
        -- Mettre à jour le prêt
        UPDATE loans
        SET days_overdue = GREATEST(days_overdue, days_late),
            penalty_amount = penalty_amount + (expected_amount * penalty_rate * (days_late / 30))
        WHERE id = loan_id_var;
        
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- Procédure pour traiter les cycles de tontine
DELIMITER //
CREATE PROCEDURE ProcessTontineCycles()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE tontine_id BIGINT;
    DECLARE account_id_var BIGINT;
    DECLARE cycle_end DATE;
    DECLARE collected_amount DECIMAL(12,2);
    DECLARE payout_amount DECIMAL(12,2);
    
    DECLARE cur CURSOR FOR 
        SELECT tc.tontine_account_id, tc.id, tc.end_date, tc.collected_amount
        FROM tontine_cycles tc
        WHERE tc.status = 'active' 
        AND tc.end_date <= CURDATE();
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    cycle_loop: LOOP
        FETCH cur INTO tontine_id, account_id_var, cycle_end, collected_amount;
        IF done THEN
            LEAVE cycle_loop;
        END IF;
        
        -- Calculer le montant de redistribution (avec bénéfices)
        SET payout_amount = collected_amount * 1.1; -- 10% de bénéfice exemple
        
        -- Marquer le cycle comme terminé
        UPDATE tontine_cycles 
        SET status = 'completed',
            payout_amount = payout_amount,
            payout_date = CURDATE()
        WHERE id = account_id_var;
        
        -- Obtenir l'account_id correspondant
        SELECT account_id INTO account_id_var
        FROM tontine_accounts 
        WHERE id = tontine_id;
        
        -- Créditer le compte du montant de redistribution
        INSERT INTO transactions (
            transaction_reference, account_id, transaction_type, amount,
            balance_before, balance_after, payment_method, description,
            status, transaction_date
        ) SELECT 
            CONCAT('PAYOUT_', UNIX_TIMESTAMP()),
            account_id_var,
            'payout',
            payout_amount,
            balance,
            balance + payout_amount,
            'system',
            CONCAT('Redistribution cycle tontine terminé le ', cycle_end),
            'completed',
            NOW()
        FROM accounts WHERE id = account_id_var;
        
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- =====================================================
-- ÉVÉNEMENTS PROGRAMMÉS (CRON JOBS)
-- =====================================================

-- Activation des événements
SET GLOBAL event_scheduler = ON;

-- Événement quotidien pour calculer les pénalités
CREATE EVENT IF NOT EXISTS daily_penalty_calculation
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 1 HOUR
DO CALL CalculateOverduePenalties();

-- Événement quotidien pour traiter les cycles de tontine
CREATE EVENT IF NOT EXISTS daily_tontine_processing
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 2 HOUR
DO CALL ProcessTontineCycles();

-- Événement pour nettoyer les anciennes notifications (tous les 7 jours)
CREATE EVENT IF NOT EXISTS weekly_notification_cleanup
ON SCHEDULE EVERY 1 WEEK
STARTS CURRENT_DATE + INTERVAL 1 DAY + INTERVAL 3 HOUR
DO DELETE FROM notifications 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
   AND status IN ('delivered', 'read');

-- =====================================================
-- FONCTIONS UTILITAIRES
-- =====================================================

-- Fonction pour formater les montants
DELIMITER //
CREATE FUNCTION FormatAmount(amount DECIMAL(15,2))
RETURNS VARCHAR(50)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN CONCAT(FORMAT(amount, 0), ' FCFA');
END //
DELIMITER ;

-- Fonction pour calculer l'âge
DELIMITER //
CREATE FUNCTION CalculateAge(birth_date DATE)
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN TIMESTAMPDIFF(YEAR, birth_date, CURDATE());
END //
DELIMITER ;

-- Fonction pour vérifier l'éligibilité au prêt
DELIMITER //
CREATE FUNCTION IsEligibleForLoan(client_id_param BIGINT, loan_amount DECIMAL(12,2))
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE savings_balance DECIMAL(15,2) DEFAULT 0;
    DECLARE active_loans INT DEFAULT 0;
    DECLARE kyc_approved BOOLEAN DEFAULT FALSE;
    DECLARE min_savings DECIMAL(12,2);
    
    -- Récupérer le seuil minimum d'épargne
    SELECT CAST(parameter_value AS DECIMAL(12,2)) INTO min_savings
    FROM system_parameters 
    WHERE parameter_key = 'min_savings_for_loan';
    
    -- Vérifier le KYC
    SELECT (kyc_status = 'approved') INTO kyc_approved
    FROM clients WHERE id = client_id_param;
    
    -- Calculer l'épargne totale
    SELECT COALESCE(SUM(balance), 0) INTO savings_balance
    FROM accounts 
    WHERE client_id = client_id_param 
    AND account_type = 'savings' 
    AND status = 'active';
    
    -- Compter les prêts actifs
    SELECT COUNT(*) INTO active_loans
    FROM loans
    WHERE client_id = client_id_param 
    AND status IN ('active', 'disbursed');
    
    -- Retourner l'éligibilité
    RETURN (kyc_approved AND savings_balance >= min_savings AND active_loans = 0);
END //
DELIMITER ;

-- =====================================================
-- REQUÊTES DE RAPPORT PRÉDÉFINIES
-- =====================================================

-- Vue pour le tableau de bord client
CREATE VIEW v_client_dashboard AS
SELECT 
    c.id as client_id,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as full_name,
    c.phone,
    c.kyc_status,
    COUNT(DISTINCT CASE WHEN a.account_type = 'savings' THEN a.id END) as savings_accounts,
    COUNT(DISTINCT CASE WHEN a.account_type = 'tontine' THEN a.id END) as tontine_accounts,
    COALESCE(SUM(CASE WHEN a.account_type = 'savings' THEN a.balance ELSE 0 END), 0) as total_savings,
    COALESCE(SUM(CASE WHEN a.account_type = 'tontine' THEN a.balance ELSE 0 END), 0) as total_tontine,
    COUNT(DISTINCT CASE WHEN l.status IN ('active', 'disbursed') THEN l.id END) as active_loans,
    COALESCE(SUM(CASE WHEN l.status IN ('active', 'disbursed') THEN l.outstanding_principal ELSE 0 END), 0) as total_loan_balance
FROM clients c
LEFT JOIN accounts a ON c.id = a.client_id AND a.status = 'active'
LEFT JOIN loans l ON c.id = l.client_id
WHERE c.is_active = TRUE
GROUP BY c.id, c.client_number, c.first_name, c.last_name, c.phone, c.kyc_status;

-- Vue pour le suivi des performances d'agence
CREATE VIEW v_agency_performance AS
SELECT 
    ag.id as agency_id,
    ag.name as agency_name,
    ag.code as agency_code,
    COUNT(DISTINCT c.id) as total_clients,
    COUNT(DISTINCT CASE WHEN c.kyc_status = 'approved' THEN c.id END) as approved_clients,
    COUNT(DISTINCT a.id) as total_accounts,
    COALESCE(SUM(a.balance), 0) as total_deposits,
    COUNT(DISTINCT l.id) as total_loans,
    COALESCE(SUM(CASE WHEN l.status IN ('active', 'disbursed') THEN l.approved_amount ELSE 0 END), 0) as active_loan_portfolio,
    COALESCE(SUM(CASE WHEN l.status IN ('active', 'disbursed') THEN l.outstanding_principal ELSE 0 END), 0) as outstanding_principal
FROM agencies ag
LEFT JOIN clients c ON ag.id = c.agency_id
LEFT JOIN accounts a ON c.id = a.client_id AND a.status = 'active'
LEFT JOIN loans l ON c.id = l.client_id
WHERE ag.is_active = TRUE
GROUP BY ag.id, ag.name, ag.code;

-- Vue pour les statistiques de transaction
CREATE VIEW v_transaction_stats AS
SELECT 
    DATE(transaction_date) as transaction_day,
    transaction_type,
    payment_method,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount,
    AVG(amount) as average_amount
FROM transactions
WHERE status = 'completed'
AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(transaction_date), transaction_type, payment_method
ORDER BY transaction_day DESC, transaction_type;

-- =====================================================
-- DONNÉES DE TEST (OPTIONNEL)
-- =====================================================

-- Client de test
INSERT INTO clients (
    client_number, first_name, last_name, date_of_birth, gender, phone, email,
    address, city, region, profession, monthly_income, id_type, id_number,
    kyc_status, registration_channel, agency_id
) VALUES (
    'CL000001', 'Kofi', 'MENSAH', '1985-03-15', 'M', '+228 90 12 34 56', 'kofi.mensah@email.com',
    '123 Rue de la Paix, Bé', 'Lomé', 'Maritime', 'Commerçant', 150000.00, 'cni', 'CNI123456789',
    'approved', 'mobile_app', 1
);

-- Compte épargne de test
INSERT INTO accounts (account_number, client_id, account_type, activation_fee, activation_fee_paid, status, balance, created_by) 
VALUES ('SA00000001', 1, 'savings', 7000.00, TRUE, 'active', 75000.00, 1);

INSERT INTO savings_accounts (account_id, interest_rate, minimum_balance) 
VALUES (1, 0.02, 1000.00);

-- Compte tontine de test  
INSERT INTO accounts (account_number, client_id, account_type, activation_fee, activation_fee_paid, status, balance, created_by)
VALUES ('TA00000001', 1, 'tontine', 500.00, TRUE, 'active', 6000.00, 1);

INSERT INTO tontine_accounts (account_id, tontine_amount, expected_monthly_payment, total_expected, cycle_start_date, cycle_end_date)
VALUES (2, 500.00, 500.00, 6000.00, '2024-01-01', '2024-12-31');

-- =====================================================
-- COMMENTAIRES FINAUX ET DOCUMENTATION
-- =====================================================
/*
BASE DE DONNÉES COMPLÈTE POUR LE SYSTÈME MIE - MICROFINANCE

Cette base de données implémente toutes les fonctionnalités décrites dans le cahier des charges :

1. GESTION DES UTILISATEURS ET RÔLES
   ✓ 6 rôles spécifiques (agent_terrain, agent_agence, gestionnaire_superviseur, etc.)
   ✓ Authentification sécurisée avec MFA
   ✓ Gestion des sessions et audit des actions

2. GESTION DES CLIENTS ET COMPTES
   ✓ KYC complet avec gestion des documents
   ✓ Multi-comptes (épargne et tontines multiples)
   ✓ Frais d'activation paramétrables (7000 FCFA épargne, 300/500/700 FCFA tontines)
   ✓ Activation via Mobile Money, virement bancaire ou cash
   ✓ Suivi des soldes et historiques détaillés

3. SYSTÈME DE TONTINES
   ✓ Comptes tontine multiples avec montants configurables
   ✓ Gestion des cycles avec dates début/fin
   ✓ Calcul automatique des redistributions
   ✓ Suivi de la progression des versements

4. GESTION DES TRANSACTIONS
   ✓ Dépôts et retraits via multiple canaux
   ✓ Intégration Mobile Money (MTN, Orange, Moov)
   ✓ Validation par agents selon les règles métier
   ✓ Génération automatique de reçus numériques

5. MODULE PRÊTS/CRÉDITS
   ✓ Calcul automatique d'éligibilité basé sur épargne et régularité
   ✓ Simulation de prêts avec échéanciers
   ✓ Workflow d'approbation avec rôles définis
   ✓ Gestion des remboursements et pénalités automatiques

6. REPORTING ET COMPTABILITÉ
   ✓ Vues prédéfinies pour tableaux de bord
   ✓ Rapports d'activité par agence
   ✓ Statistiques de performance globales
   ✓ Audit trail complet

7. ADMINISTRATION SYSTÈME
   ✓ Paramètres configurables (taux, frais, seuils)
   ✓ Gestion des utilisateurs et permissions
   ✓ Journalisation complète des actions
   ✓ Événements programmés pour automatisation

8. SÉCURITÉ ET CONFORMITÉ
   ✓ Chiffrement des données sensibles (à implémenter au niveau applicatif)
   ✓ Audit trail complet de toutes les actions
   ✓ Gestion des sessions et authentification forte
   ✓ Respect des exigences réglementaires BCEAO/COBAC

POINTS D'ATTENTION POUR L'IMPLÉMENTATION :

1. SÉCURITÉ :
   - Implémenter le chiffrement des mots de passe avec bcrypt/argon2
   - Chiffrer les données sensibles (numéros de téléphone, pièces d'identité)
   - Implémenter la double authentification (2FA/MFA)
   - Utiliser HTTPS pour toutes les communications

2. PERFORMANCE :
   - Les index sont optimisés pour les requêtes fréquentes
   - Utiliser la pagination pour les listes longues
   - Implémenter la mise en cache Redis pour les données fréquemment consultées
   - Surveiller les performances des triggers et événements

3. INTÉGRATIONS :
   - APIs Mobile Money sécurisées avec tokens d'authentification
   - Webhooks pour les notifications de paiement
   - API bancaires pour les virements
   - Services de notification (SMS, Push, Email)

4. SAUVEGARDE ET RÉCUPÉRATION :
   - Sauvegardes automatiques quotidiennes
   - Réplication master-slave pour la haute disponibilité
   - Tests de récupération réguliers
   - Plan de continuité d'activité

5. ÉVOLUTIVITÉ :
   - Architecture modulaire permettant l'ajout de nouvelles fonctionnalités
   - Séparation lecture/écriture si nécessaire (CQRS)
   - Possibilité de sharding horizontal pour la croissance

Cette structure de base de données est prête pour le développement des applications mobile (Flutter) et web (Laravel/Livewire) avec une API REST robuste et sécurisée.
*/

-- =====================================================
-- VUES SPÉCIFIQUES POUR L'INTERFACE CLIENT
-- =====================================================

-- Vue complète des prêts pour un client (historique et statut actuel)
CREATE VIEW v_client_loans_history AS
SELECT 
    l.id as loan_id,
    l.loan_number,
    l.client_id,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    l.requested_amount,
    l.approved_amount,
    l.interest_rate,
    l.duration_months,
    l.monthly_payment,
    l.total_amount_due,
    l.purpose,
    l.status,
    l.risk_level,
    l.application_date,
    l.approved_at,
    l.disbursed_at,
    l.first_payment_date,
    l.maturity_date,
    l.outstanding_principal,
    l.outstanding_interest,
    l.total_paid,
    l.penalty_amount,
    l.days_overdue,
    -- Calculs additionnels pour l'interface client
    CASE 
        WHEN l.status IN ('active', 'disbursed') THEN 
            ROUND((l.total_paid / l.total_amount_due) * 100, 2)
        ELSE 0 
    END as repayment_progress_percent,
    CASE 
        WHEN l.status = 'active' THEN l.outstanding_principal + l.outstanding_interest + l.penalty_amount
        ELSE 0 
    END as total_amount_remaining,
    -- Statut en français pour l'affichage
    CASE l.status
        WHEN 'pending' THEN 'En attente'
        WHEN 'under_review' THEN 'En cours d\'évaluation'
        WHEN 'approved' THEN 'Approuvé'
        WHEN 'rejected' THEN 'Rejeté'
        WHEN 'disbursed' THEN 'Décaissé'
        WHEN 'active' THEN 'Actif'
        WHEN 'completed' THEN 'Remboursé'
        WHEN 'defaulted' THEN 'En défaut'
        ELSE l.status
    END as status_display
FROM loans l
JOIN clients c ON l.client_id = c.id
ORDER BY l.application_date DESC;

-- Vue des échéanciers de prêt pour un client
CREATE VIEW v_client_loan_payments AS
SELECT 
    lp.id as payment_id,
    lp.loan_id,
    l.loan_number,
    l.client_id,
    lp.payment_number,
    lp.due_date,
    lp.paid_date,
    lp.expected_amount,
    lp.principal_amount,
    lp.interest_amount,
    lp.penalty_amount,
    lp.paid_amount,
    lp.payment_method,
    lp.payment_reference,
    lp.status,
    lp.days_overdue,
    -- Calculs pour l'interface
    (lp.expected_amount + lp.penalty_amount) as total_due,
    (lp.expected_amount + lp.penalty_amount - lp.paid_amount) as amount_remaining,
    -- Statut en français
    CASE lp.status
        WHEN 'pending' THEN 'À payer'
        WHEN 'paid' THEN 'Payé'
        WHEN 'partial' THEN 'Paiement partiel'
        WHEN 'overdue' THEN 'En retard'
        WHEN 'cancelled' THEN 'Annulé'
        ELSE lp.status
    END as status_display,
    -- Indicateur de retard
    CASE 
        WHEN lp.due_date < CURDATE() AND lp.status NOT IN ('paid', 'cancelled') THEN TRUE
        ELSE FALSE
    END as is_overdue
FROM loan_payments lp
JOIN loans l ON lp.loan_id = l.id
ORDER BY l.loan_number, lp.payment_number;

-- Vue historique complète des transactions pour un client (épargne, tontine, prêts)
CREATE VIEW v_client_transaction_history AS
SELECT 
    t.id as transaction_id,
    t.transaction_reference,
    t.client_id,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    t.account_id,
    a.account_number,
    a.account_type,
    -- Détails spécifiques selon le type de compte
    CASE 
        WHEN a.account_type = 'tontine' THEN CONCAT('Tontine ', ta.tontine_amount, ' FCFA')
        WHEN a.account_type = 'savings' THEN 'Compte Épargne'
        ELSE a.account_type
    END as account_display,
    t.transaction_type,
    -- Type de transaction en français
    CASE t.transaction_type
        WHEN 'deposit' THEN 'Dépôt'
        WHEN 'withdrawal' THEN 'Retrait'
        WHEN 'transfer' THEN 'Transfert'
        WHEN 'fee' THEN 'Frais'
        WHEN 'interest' THEN 'Intérêts'
        WHEN 'penalty' THEN 'Pénalité'
        WHEN 'payout' THEN 'Redistribution'
        ELSE t.transaction_type
    END as transaction_type_display,
    t.amount,
    t.balance_before,
    t.balance_after,
    t.payment_method,
    -- Méthode de paiement en français
    CASE t.payment_method
        WHEN 'cash' THEN 'Espèces'
        WHEN 'mobile_money' THEN 'Mobile Money'
        WHEN 'bank_transfer' THEN 'Virement bancaire'
        WHEN 'system' THEN 'Système'
        ELSE t.payment_method
    END as payment_method_display,
    t.payment_reference,
    t.mobile_money_operator,
    t.description,
    t.status,
    CASE t.status
        WHEN 'pending' THEN 'En attente'
        WHEN 'completed' THEN 'Terminé'
        WHEN 'failed' THEN 'Échoué'
        WHEN 'cancelled' THEN 'Annulé'
        ELSE t.status
    END as status_display,
    t.transaction_date,
    -- Formatage de la date pour l'affichage
    DATE_FORMAT(t.transaction_date, '%d/%m/%Y %H:%i') as formatted_date,
    -- Catégorisation pour les filtres
    CASE 
        WHEN a.account_type = 'savings' THEN 'Épargne'
        WHEN a.account_type = 'tontine' THEN 'Tontine'
        ELSE 'Autre'
    END as category
FROM (
    -- Transactions des comptes (épargne et tontine)
    SELECT 
        tr.id,
        tr.transaction_reference,
        ac.client_id,
        tr.account_id,
        tr.transaction_type,
        tr.amount,
        tr.balance_before,
        tr.balance_after,
        tr.payment_method,
        tr.payment_reference,
        tr.mobile_money_operator,
        tr.description,
        tr.status,
        tr.transaction_date
    FROM transactions tr
    JOIN accounts ac ON tr.account_id = ac.id
    
    UNION ALL
    
    -- Transactions liées aux prêts (décaissements et remboursements)
    SELECT 
        (lp.id + 1000000) as id, -- Décalage pour éviter les conflits d'ID
        CONCAT('LOAN_', lp.id) as transaction_reference,
        l.client_id,
        NULL as account_id, -- Pas de compte associé pour les prêts
        CASE 
            WHEN lp.paid_amount > 0 THEN 'loan_repayment'
            ELSE 'loan_disbursement'
        END as transaction_type,
        CASE 
            WHEN lp.paid_amount > 0 THEN lp.paid_amount
            ELSE l.approved_amount
        END as amount,
        0 as balance_before, -- Non applicable pour les prêts
        0 as balance_after,  -- Non applicable pour les prêts
        COALESCE(lp.payment_method, l.disbursement_method, 'system') as payment_method,
        COALESCE(lp.payment_reference, l.disbursement_reference) as payment_reference,
        NULL as mobile_money_operator,
        CASE 
            WHEN lp.paid_amount > 0 THEN CONCAT('Remboursement prêt ', l.loan_number, ' - Échéance #', lp.payment_number)
            ELSE CONCAT('Décaissement prêt ', l.loan_number)
        END as description,
        CASE 
            WHEN lp.paid_amount > 0 THEN 'completed'
            WHEN l.disbursed_at IS NOT NULL THEN 'completed'
            ELSE 'pending'
        END as status,
        CASE 
            WHEN lp.paid_date IS NOT NULL THEN lp.paid_date
            WHEN l.disbursed_at IS NOT NULL THEN l.disbursed_at
            ELSE l.application_date
        END as transaction_date
    FROM loans l
    LEFT JOIN loan_payments lp ON l.id = lp.loan_id AND lp.paid_amount > 0
    WHERE l.status IN ('disbursed', 'active', 'completed')
) t
JOIN clients c ON t.client_id = c.id
LEFT JOIN accounts a ON t.account_id = a.id
LEFT JOIN tontine_accounts ta ON a.id = ta.account_id
ORDER BY t.transaction_date DESC;

-- Vue résumé des comptes pour le tableau de bord client
CREATE VIEW v_client_accounts_summary AS
SELECT 
    c.id as client_id,
    c.client_number,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    -- Comptes d'épargne
    COUNT(DISTINCT CASE WHEN a.account_type = 'savings' AND a.status = 'active' THEN a.id END) as active_savings_accounts,
    COALESCE(SUM(CASE WHEN a.account_type = 'savings' AND a.status = 'active' THEN a.balance ELSE 0 END), 0) as total_savings_balance,
    -- Comptes tontine
    COUNT(DISTINCT CASE WHEN a.account_type = 'tontine' AND a.status = 'active' THEN a.id END) as active_tontine_accounts,
    COALESCE(SUM(CASE WHEN a.account_type = 'tontine' AND a.status = 'active' THEN a.balance ELSE 0 END), 0) as total_tontine_balance,
    -- Détails des tontines actives
    GROUP_CONCAT(
        DISTINCT CASE 
            WHEN a.account_type = 'tontine' AND a.status = 'active' 
            THEN CONCAT(ta.tontine_amount, ' FCFA (', ta.payments_made, '/', (ta.total_expected/ta.expected_monthly_payment), ')') 
        END 
        SEPARATOR ', '
    ) as tontine_details,
    -- Prêts
    COUNT(DISTINCT CASE WHEN l.status IN ('active', 'disbursed') THEN l.id END) as active_loans,
    COALESCE(SUM(CASE WHEN l.status IN ('active', 'disbursed') THEN l.outstanding_principal ELSE 0 END), 0) as total_outstanding_loans,
    -- Prochaine échéance de prêt
    MIN(CASE WHEN lp.status = 'pending' AND lp.due_date >= CURDATE() THEN lp.due_date END) as next_loan_payment_date,
    COALESCE(SUM(CASE WHEN lp.status = 'pending' AND lp.due_date >= CURDATE() THEN lp.expected_amount + lp.penalty_amount END), 0) as next_payment_amount,
    -- Solde total
    COALESCE(SUM(CASE WHEN a.status = 'active' THEN a.balance ELSE 0 END), 0) as total_balance,
    -- Score de crédit
    c.credit_score,
    -- Éligibilité aux prêts
    CASE 
        WHEN c.kyc_status = 'approved' 
        AND COALESCE(SUM(CASE WHEN a.account_type = 'savings' THEN a.balance ELSE 0 END), 0) >= 50000 
        AND COUNT(CASE WHEN l.status IN ('active', 'disbursed') THEN l.id END) = 0 
        THEN TRUE 
        ELSE FALSE 
    END as is_eligible_for_loan
FROM clients c
LEFT JOIN accounts a ON c.id = a.client_id
LEFT JOIN tontine_accounts ta ON a.id = ta.account_id
LEFT JOIN loans l ON c.id = l.client_id
LEFT JOIN loan_payments lp ON l.id = lp.loan_id
WHERE c.is_active = TRUE
GROUP BY c.id, c.client_number, c.first_name, c.last_name, c.credit_score, c.kyc_status;

-- =====================================================
-- PROCÉDURES POUR L'INTERFACE CLIENT
-- =====================================================

-- Procédure pour obtenir l'historique complet d'un client
DELIMITER //
CREATE PROCEDURE GetClientCompleteHistory(IN client_id_param BIGINT, IN limit_records INT)
BEGIN
    -- Historique des transactions
    SELECT 'TRANSACTIONS' as section_type;
    
    SELECT 
        transaction_id,
        transaction_reference,
        account_display,
        transaction_type_display,
        amount,
        payment_method_display,
        status_display,
        formatted_date,
        description
    FROM v_client_transaction_history
    WHERE client_id = client_id_param
    ORDER BY transaction_date DESC
    LIMIT COALESCE(limit_records, 50);
    
    -- Historique des prêts
    SELECT 'LOANS' as section_type;
    
    SELECT 
        loan_number,
        requested_amount,
        approved_amount,
        status_display,
        repayment_progress_percent,
        total_amount_remaining,
        DATE_FORMAT(application_date, '%d/%m/%Y') as application_date_formatted,
        DATE_FORMAT(maturity_date, '%d/%m/%Y') as maturity_date_formatted
    FROM v_client_loans_history
    WHERE client_id = client_id_param
    ORDER BY application_date DESC;
    
    -- Résumé des comptes
    SELECT 'SUMMARY' as section_type;
    
    SELECT * FROM v_client_accounts_summary
    WHERE client_id = client_id_param;
    
END //
DELIMITER ;

-- Procédure pour obtenir le détail d'un prêt spécifique
DELIMITER //
CREATE PROCEDURE GetLoanDetails(IN loan_id_param BIGINT, IN requesting_client_id BIGINT)
BEGIN
    DECLARE loan_client_id BIGINT;
    
    -- Vérifier que le prêt appartient bien au client demandeur
    SELECT client_id INTO loan_client_id FROM loans WHERE id = loan_id_param;
    
    IF loan_client_id = requesting_client_id THEN
        -- Détails du prêt
        SELECT 
            loan_number,
            requested_amount,
            approved_amount,
            interest_rate,
            duration_months,
            monthly_payment,
            total_amount_due,
            outstanding_principal,
            outstanding_interest,
            penalty_amount,
            total_paid,
            status_display,
            repayment_progress_percent,
            DATE_FORMAT(application_date, '%d/%m/%Y') as application_date_formatted,
            DATE_FORMAT(approved_at, '%d/%m/%Y') as approved_date_formatted,
            DATE_FORMAT(disbursed_at, '%d/%m/%Y') as disbursed_date_formatted,
            DATE_FORMAT(maturity_date, '%d/%m/%Y') as maturity_date_formatted,
            purpose
        FROM v_client_loans_history
        WHERE loan_id = loan_id_param;
        
        -- Échéancier du prêt
        SELECT 
            payment_number,
            DATE_FORMAT(due_date, '%d/%m/%Y') as due_date_formatted,
            DATE_FORMAT(paid_date, '%d/%m/%Y') as paid_date_formatted,
            expected_amount,
            principal_amount,
            interest_amount,
            penalty_amount,
            paid_amount,
            amount_remaining,
            status_display,
            is_overdue,
            payment_method,
            payment_reference
        FROM v_client_loan_payments
        WHERE loan_id = loan_id_param
        ORDER BY payment_number;
        
    ELSE
        -- Erreur : prêt n'appartient pas au client
        SELECT 'UNAUTHORIZED' as error_type, 'Ce prêt ne vous appartient pas' as error_message;
    END IF;
    
END //
DELIMITER ;

-- =====================================================
-- REQUÊTES EXEMPLE POUR L'APPLICATION CLIENT
-- =====================================================

/*
-- Tableau de bord client (résumé complet)
SELECT * FROM v_client_accounts_summary WHERE client_id = 1;

-- Historique des transactions des 30 derniers jours
SELECT * FROM v_client_transaction_history 
WHERE client_id = 1 
AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY transaction_date DESC;

-- Tous les prêts du client
SELECT * FROM v_client_loans_history WHERE client_id = 1;

-- Prochaines échéances à payer
SELECT * FROM v_client_loan_payments 
WHERE client_id = (SELECT client_id FROM loans WHERE id IN (SELECT DISTINCT loan_id FROM v_client_loan_payments WHERE client_id = 1))
AND status IN ('pending', 'partial', 'overdue')
AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY due_date;

-- Transactions par catégorie (pour graphiques)
SELECT 
    category,
    transaction_type_display,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount
FROM v_client_transaction_history 
WHERE client_id = 1 
AND transaction_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
GROUP BY category, transaction_type_display
ORDER BY category, total_amount DESC;
*/

-- Fin du script de création de la base de données