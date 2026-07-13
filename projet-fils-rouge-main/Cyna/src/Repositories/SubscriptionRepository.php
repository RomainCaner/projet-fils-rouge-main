<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Abonnements aux services SaaS (gérés depuis l'espace compte).
 * Base en français ; colonnes aliasées vers les clés applicatives.
 */
final class SubscriptionRepository
{
    private const SELECT = 'id, utilisateur_id AS user_id, produit_id AS product_id, commande_id AS order_id,
        produit_nom AS product_name, periodicite AS billing_period, quantite AS quantity, statut AS status,
        renouvellement_auto AS auto_renew, debute_le AS started_at, renouvelle_le AS renews_at, resilie_le AS cancelled_at';

    /** @return list<array<string,mixed>> */
    public static function forUser(int $userId): array
    {
        // Traite d'abord les échéances (renouvellement auto ou expiration).
        self::processDue($userId);

        return Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM abonnements WHERE utilisateur_id = ? ORDER BY statut = "active" DESC, debute_le DESC',
            [$userId],
        );
    }

    /** @return array<string,mixed>|null */
    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM abonnements WHERE id = ? AND utilisateur_id = ?',
            [$id, $userId],
        );
    }

    public static function create(
        int $userId,
        ?int $productId,
        int $orderId,
        string $productName,
        string $billingPeriod,
        int $quantity,
    ): int {
        // L'échéance dépend de la périodicité (mensuelle ou annuelle).
        $interval = $billingPeriod === 'annual' ? 'INTERVAL 1 YEAR' : 'INTERVAL 1 MONTH';
        Database::run(
            "INSERT INTO abonnements (utilisateur_id, produit_id, commande_id, produit_nom, periodicite, quantite, statut, renouvelle_le)
             VALUES (?, ?, ?, ?, ?, ?, 'active', DATE_ADD(NOW(), {$interval}))",
            [$userId, $productId, $orderId, $productName, $billingPeriod, $quantity],
        );

        return Database::lastInsertId();
    }

    /** Renouvelle un abonnement actif en repoussant l'échéance selon sa périodicité. */
    public static function renew(int $id, int $userId): void
    {
        $subscription = self::findForUser($id, $userId);
        if ($subscription === null || $subscription['status'] !== 'active') {
            return;
        }

        $interval = $subscription['billing_period'] === 'annual' ? 'INTERVAL 1 YEAR' : 'INTERVAL 1 MONTH';
        Database::run(
            "UPDATE abonnements
             SET renouvelle_le = DATE_ADD(GREATEST(COALESCE(renouvelle_le, NOW()), NOW()), {$interval})
             WHERE id = ? AND utilisateur_id = ?",
            [$id, $userId],
        );
    }

    /**
     * Résilie un abonnement : on désactive le renouvellement automatique, mais
     * le service reste ACTIF jusqu'à la fin de la période courante (renouvelle_le).
     */
    public static function cancel(int $id, int $userId): void
    {
        Database::run(
            'UPDATE abonnements SET renouvellement_auto = 0, resilie_le = NOW()
             WHERE id = ? AND utilisateur_id = ? AND statut = "active"',
            [$id, $userId],
        );
    }

    /** Réactive le renouvellement automatique d'un abonnement résilié mais encore actif. */
    public static function reactivate(int $id, int $userId): void
    {
        Database::run(
            'UPDATE abonnements SET renouvellement_auto = 1, resilie_le = NULL
             WHERE id = ? AND utilisateur_id = ? AND statut = "active"',
            [$id, $userId],
        );
    }

    /**
     * Traite les abonnements arrivés à échéance pour un utilisateur :
     *   • renouvellement_auto = 1 → on repousse l'échéance (renouvellement) ;
     *   • renouvellement_auto = 0 → l'abonnement passe à « expiré ».
     * (En production, un tâche planifiée jouerait ce traitement ; ici il est
     * déclenché paresseusement à la consultation.)
     */
    public static function processDue(int $userId): void
    {
        // Expiration des abonnements résiliés dont l'échéance est passée.
        Database::run(
            'UPDATE abonnements SET statut = "expired"
             WHERE utilisateur_id = ? AND statut = "active" AND renouvellement_auto = 0
               AND renouvelle_le IS NOT NULL AND renouvelle_le < NOW()',
            [$userId],
        );

        // Renouvellement automatique : on repousse l'échéance période par période.
        Database::run(
            'UPDATE abonnements
             SET renouvelle_le = DATE_ADD(renouvelle_le, INTERVAL 1 MONTH)
             WHERE utilisateur_id = ? AND statut = "active" AND renouvellement_auto = 1
               AND periodicite = "monthly" AND renouvelle_le IS NOT NULL AND renouvelle_le < NOW()',
            [$userId],
        );
        Database::run(
            'UPDATE abonnements
             SET renouvelle_le = DATE_ADD(renouvelle_le, INTERVAL 1 YEAR)
             WHERE utilisateur_id = ? AND statut = "active" AND renouvellement_auto = 1
               AND periodicite = "annual" AND renouvelle_le IS NOT NULL AND renouvelle_le < NOW()',
            [$userId],
        );
    }

    // --------------------------------------------------- Back-office (admin)

    /**
     * Liste paginée de tous les abonnements, avec l'e-mail du client.
     *
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function paginate(int $offset, int $perPage, string $status = ''): array
    {
        $where = '';
        $params = [];
        if ($status !== '') {
            $where = ' WHERE a.statut = ?';
            $params[] = $status;
        }

        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM abonnements a' . $where, $params);
        $items = Database::fetchAll(
            'SELECT a.id, a.produit_nom AS product_name, a.periodicite AS billing_period, a.quantite AS quantity,
                    a.statut AS status, a.renouvellement_auto AS auto_renew, a.debute_le AS started_at,
                    a.renouvelle_le AS renews_at, a.resilie_le AS cancelled_at, u.email AS user_email
             FROM abonnements a
             LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id'
             . $where . ' ORDER BY a.debute_le DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params,
        );

        return ['items' => $items, 'total' => $total];
    }
}
