-- ===========================================================================
-- Migration : titre (libellé) des codes de réduction
-- ---------------------------------------------------------------------------
--   mysql -u cyna -p cyna < database/migrations/2026_07_promo_titre.sql
-- ===========================================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'codes_reduction' AND COLUMN_NAME = 'titre'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE codes_reduction ADD COLUMN titre VARCHAR(120) NULL AFTER code',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE codes_reduction SET titre = 'Offre de bienvenue'    WHERE code = 'BIENVENUE10' AND titre IS NULL;
UPDATE codes_reduction SET titre = 'Promotion Cyna -25%'   WHERE code = 'CYNA25'      AND titre IS NULL;
UPDATE codes_reduction SET titre = 'Remise sécurité 50€'   WHERE code = 'SECURE50'    AND titre IS NULL;
