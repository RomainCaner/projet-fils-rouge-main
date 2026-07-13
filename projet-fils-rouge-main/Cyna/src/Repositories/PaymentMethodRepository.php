<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Moyens de paiement enregistrés.
 *
 * Seules des informations non sensibles sont conservées (marque, 4 derniers
 * chiffres, échéance) ainsi qu'un identifiant opaque Stripe. Aucun numéro de
 * carte complet n'est jamais stocké. Base en français, colonnes aliasées.
 */
final class PaymentMethodRepository
{
    private const SELECT = 'id, utilisateur_id AS user_id, marque AS brand, quatre_derniers AS last4,
        mois_expiration AS exp_month, annee_expiration AS exp_year, stripe_pm_id, par_defaut AS is_default';

    /** @return list<array<string,mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM moyens_paiement WHERE utilisateur_id = ? ORDER BY par_defaut DESC, id DESC',
            [$userId],
        );
    }

    public static function create(int $userId, string $brand, string $last4, int $expMonth, int $expYear, ?string $stripeId): int
    {
        Database::run(
            'INSERT INTO moyens_paiement (utilisateur_id, marque, quatre_derniers, mois_expiration, annee_expiration, stripe_pm_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $brand, $last4, $expMonth, $expYear, $stripeId],
        );

        return Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        Database::run('DELETE FROM moyens_paiement WHERE id = ? AND utilisateur_id = ?', [$id, $userId]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        Database::run('UPDATE moyens_paiement SET par_defaut = 0 WHERE utilisateur_id = ?', [$userId]);
        Database::run('UPDATE moyens_paiement SET par_defaut = 1 WHERE id = ? AND utilisateur_id = ?', [$id, $userId]);
    }
}
