-- ==============================================================================
-- YAYRA MICROFINANCE - INITIALISATION PROPRE DE LA BASE DE DONNÉES
-- ==============================================================================

-- 1. Nettoyage des anciennes sessions et tokens
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

-- 4. Correction de la table audit_logs si nécessaire
ALTER TABLE `audit_logs` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
