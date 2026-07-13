-- ===========================================================================
-- Migration : renouvellement automatique des abonnements
-- ---------------------------------------------------------------------------
-- Ajoute l'indicateur de renouvellement automatique. La colonne renouvelle_le
-- existante fait office de « date de fin de la période courante » :
--   • renouvellement_auto = 1 → à cette date, l'abonnement se renouvelle ;
--   • renouvellement_auto = 0 (résilié) → à cette date, l'abonnement expire,
--     mais le service reste actif jusque-là.
--
--   mysql -u cyna -p cyna < database/migrations/2026_07_abonnements_auto.sql
-- ===========================================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonnements' AND COLUMN_NAME = 'renouvellement_auto'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE abonnements ADD COLUMN renouvellement_auto TINYINT(1) NOT NULL DEFAULT 1 AFTER statut',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
