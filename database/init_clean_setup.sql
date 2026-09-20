-- ==============================================================================
-- YAYRA MICROFINANCE - INITIALISATION FONDAMENTALE DE LA PLATEFORME
-- ==============================================================================

-- 1. Nettoyage des anciennes sessions et tokens orphelins
DELETE FROM `personal_access_tokens`;
DELETE FROM `sessions`;

-- 2. Configuration des Agences (Agoè Siège & Djagblé)
INSERT INTO `agencies` (`id`, `name`, `code`, `address`, `city`, `region`, `phone`, `cash_limit`, `vault_balance`, `is_active`, `created_at`, `updated_at`)
VALUES 
(1, 'Agence Agoè (Siège)', 'AGO', 'Agoè, Carrefour 2 Lions', 'Lomé', 'Maritime', '+228 90 00 00 01', 50000000.00, 0.00, 1, NOW(), NOW()),
(2, 'Agence Djagblé', 'DJG', 'Djagblé Centre', 'Djagblé', 'Maritime', '+228 90 00 00 02', 20000000.00, 0.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `code` = VALUES(`code`),
    `address` = VALUES(`address`),
    `city` = VALUES(`city`),
    `is_active` = 1,
    `updated_at` = NOW();

-- 3. Configuration de l'Administrateur Système Unique
-- Identifiant : Mieadmin360@gmail.com (ou mieadmin360)
-- Mot de passe : Mie@2026360@
INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `agency_id`, `is_active`, `mfa_enabled`, `created_at`, `updated_at`)
VALUES 
(1, 'mieadmin360', 'Super', 'Administrateur', 'Mieadmin360@gmail.com', '+22890000000', '$2y$12$0GvG5qM54oVnB2/N5u6CWev1191sWbE4xR9vVf07T12rZ6tZ.G5nO', 'administrateur_systeme', 1, 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `username` = 'mieadmin360',
    `email` = 'Mieadmin360@gmail.com',
    `password` = '$2y$12$0GvG5qM54oVnB2/N5u6CWev1191sWbE4xR9vVf07T12rZ6tZ.G5nO',
    `role` = 'administrateur_systeme',
    `agency_id` = 1,
    `is_active` = 1,
    `updated_at` = NOW();

-- 4. Configuration Complète des Paramètres Système Fondamentaux (Frais, Taux, Tontines, Règles Métier)
INSERT INTO `system_parameters` (`parameter_key`, `parameter_value`, `parameter_type`, `description`, `category`, `is_editable`, `created_at`, `updated_at`)
VALUES
-- --- FRAIS BANCAIRES ET SERVICES ---
('savings_account_activation_fee', '7000', 'number', 'Frais d\'activation compte épargne (FCFA)', 'fees', 1, NOW(), NOW()),
('tontine_carnet_fee', '1000', 'number', 'Frais d\'adhésion / carnet de tontine (FCFA)', 'fees', 1, NOW(), NOW()),
('savings_withdrawal_fee_percentage', '2.0', 'number', 'Pourcentage de commission sur retrait Épargne (%)', 'fees', 1, NOW(), NOW()),
('savings_withdrawal_fee_fixed', '0', 'number', 'Frais fixe sur retrait Épargne (FCFA)', 'fees', 1, NOW(), NOW()),
('tontine_withdrawal_fee_percentage', '3.0', 'number', 'Pourcentage de commission sur retrait Tontine (%)', 'fees', 1, NOW(), NOW()),
('tontine_withdrawal_fee_fixed', '0', 'number', 'Frais fixe sur retrait Tontine (FCFA)', 'fees', 1, NOW(), NOW()),
('loan_file_study_fee', '5000', 'number', 'Frais d\'étude de dossier de microcrédit (FCFA)', 'fees', 1, NOW(), NOW()),
('sms_notification_fee', '25', 'number', 'Frais par notification SMS envoyée (FCFA)', 'fees', 1, NOW(), NOW()),

-- --- TAUX D\'INTÉRÊT DES CRÉDITS ---
('loan_interest_rate_low', '12.0', 'number', 'Taux d\'intérêt annuel - Risque Faible (%)', 'rates', 1, NOW(), NOW()),
('loan_interest_rate_medium', '17.0', 'number', 'Taux d\'intérêt annuel - Risque Moyen (%)', 'rates', 1, NOW(), NOW()),
('loan_interest_rate_high', '20.0', 'number', 'Taux d\'intérêt annuel - Risque Élevé (%)', 'rates', 1, NOW(), NOW()),
('loan_interest_rate_default', '17.0', 'number', 'Taux d\'intérêt annuel par défaut (%)', 'rates', 1, NOW(), NOW()),
('loan_penalty_rate_daily', '0.1', 'number', 'Pénalité journalière de retard sur crédit (%)', 'rates', 1, NOW(), NOW()),

-- --- RÈGLES DE TONTINE & COLLECTE DE TERRAIN ---
('tontine_cycle_duration_days', '31', 'number', 'Durée standard d\'un cycle de tontine (Jours)', 'tontine', 1, NOW(), NOW()),
('tontine_commission_days', '1', 'number', 'Nombre de mises retenues comme commission par cycle', 'tontine', 1, NOW(), NOW()),
('agent_max_daily_cash_limit', '500000', 'number', 'Plafond maximum d\'espèces détenues par agent terrain (FCFA)', 'limits', 1, NOW(), NOW()),
('min_deposit_amount', '100', 'number', 'Montant minimum d\'un dépôt (FCFA)', 'limits', 1, NOW(), NOW()),

-- --- PARAMÈTRES GÉNÉRAUX DE LA PLATEFORME ---
('app_currency', 'XOF', 'string', 'Devise officielle de l\'institution', 'general', 0, NOW(), NOW()),
('company_name', 'YAYRA Microfinance', 'string', 'Nom légal de l\'institution', 'general', 1, NOW(), NOW()),
('company_country', 'Togo', 'string', 'Pays du siège social', 'general', 1, NOW(), NOW()),
('company_support_phone', '+228 90 00 00 00', 'string', 'Numéro du service client', 'general', 1, NOW(), NOW())

ON DUPLICATE KEY UPDATE 
    `parameter_value` = VALUES(`parameter_value`),
    `description` = VALUES(`description`),
    `category` = VALUES(`category`),
    `is_editable` = VALUES(`is_editable`),
    `updated_at` = NOW();

-- 5. Sécurisation de la table des logs d'audit
ALTER TABLE `audit_logs` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
