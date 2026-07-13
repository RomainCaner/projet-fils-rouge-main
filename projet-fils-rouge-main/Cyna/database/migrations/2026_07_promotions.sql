-- ===========================================================================
-- Migration : codes de réduction (fonctionnalité « promotions »)
-- ---------------------------------------------------------------------------
-- À exécuter sur une base existante :
--   mysql -u cyna -p cyna < database/migrations/2026_07_promotions.sql
-- (Les nouvelles installations obtiennent ces objets directement via schema.sql.)
-- ===========================================================================

-- Table des codes de réduction
CREATE TABLE IF NOT EXISTS codes_reduction (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(40) NOT NULL,
    type             ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    valeur           INT UNSIGNED NOT NULL,          -- pourcentage (1-100) ou montant en centimes
    actif            TINYINT(1) NOT NULL DEFAULT 1,
    expire_le        DATETIME NULL,                  -- NULL = pas d'expiration
    utilisations_max INT UNSIGNED NULL,              -- NULL = illimité
    utilisations     INT UNSIGNED NOT NULL DEFAULT 0,
    cree_le          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_codes_reduction_code (code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Colonnes de suivi de la remise sur les commandes (ajout idempotent)
SET @col_code := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'code_reduction'
);
SET @sql := IF(@col_code = 0,
    'ALTER TABLE commandes ADD COLUMN code_reduction VARCHAR(40) NULL AFTER total_centimes',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_remise := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'remise_centimes'
);
SET @sql := IF(@col_remise = 0,
    'ALTER TABLE commandes ADD COLUMN remise_centimes INT UNSIGNED NOT NULL DEFAULT 0 AFTER code_reduction',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Codes de démonstration
INSERT IGNORE INTO codes_reduction (code, type, valeur, actif, utilisations_max) VALUES
    ('BIENVENUE10', 'percent', 10, 1, NULL),
    ('CYNA25',      'percent', 25, 1, 100),
    ('SECURE50',    'fixed',   5000, 1, NULL);
