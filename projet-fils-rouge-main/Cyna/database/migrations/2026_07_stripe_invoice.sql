-- ===========================================================================
-- Migration : URL de la facture générée par Stripe
-- ---------------------------------------------------------------------------
-- Quand une clé Stripe est configurée, la facture est produite par Stripe et
-- son PDF hébergé est référencé ici. Sans clé, la colonne reste NULL et la
-- facture maison (InvoicePdf) est utilisée en repli.
--
--   mysql -u cyna -p cyna < database/migrations/2026_07_stripe_invoice.sql
-- ===========================================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'stripe_invoice_url'
);
SET @sql := IF(@col = 0,
    'ALTER TABLE commandes ADD COLUMN stripe_invoice_url VARCHAR(255) NULL AFTER stripe_payment_intent',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
