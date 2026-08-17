-- Afficher tous les comptes tontine avec leur statut
SELECT 
    a.id,
    a.account_number,
    a.status,
    c.first_name,
    c.last_name,
    t.total_paid
FROM accounts a
JOIN clients c ON a.client_id = c.id
LEFT JOIN tontine_accounts t ON t.account_id = a.id
WHERE a.account_type = 'tontine'
ORDER BY a.id;

-- Réactiver un compte tontine suspendu (remplacez [ID_DU_COMPTE] par l'ID réel)
UPDATE accounts 
SET 
    status = 'active',
    suspension_reason = NULL,
    activated_at = NOW(),
    suspended_at = NULL,
    suspended_by = NULL
WHERE id = [ID_DU_COMPTE];

-- Vérifier le changement
SELECT id, account_number, status, activated_at 
FROM accounts 
WHERE id = [ID_DU_COMPTE];
