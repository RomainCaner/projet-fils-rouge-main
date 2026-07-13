<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Accès aux commandes et lignes de commande.
 *
 * Gère la création transactionnelle d'une commande, l'historique client et
 * les statistiques de vente du tableau de bord. Base en français : colonnes
 * aliasées vers les clés applicatives.
 */
final class OrderRepository
{
    /** Statuts comptabilisés comme une vente effective. */
    private const SALE_STATUSES = ['paid', 'active', 'renewed'];

    private const SELECT = 'id, utilisateur_id AS user_id, numero_facture AS invoice_number, email,
        statut AS status, total_centimes AS total_cents, code_reduction AS discount_code,
        remise_centimes AS discount_cents, devise AS currency,
        facturation_nom AS billing_name, facturation_ligne1 AS billing_line1, facturation_ligne2 AS billing_line2,
        facturation_ville AS billing_city, facturation_region AS billing_region,
        facturation_code_postal AS billing_postal_code, facturation_pays AS billing_country,
        paiement_marque AS payment_brand, paiement_quatre_derniers AS payment_last4,
        stripe_payment_intent, stripe_invoice_url, cree_le AS created_at';

    private const SELECT_ITEMS = 'id, commande_id AS order_id, produit_id AS product_id, produit_nom AS product_name,
        periodicite AS billing_period, quantite AS quantity, prix_unitaire_centimes AS unit_price_cents,
        total_ligne_centimes AS line_total_cents';

    /**
     * Crée une commande et ses lignes au sein d'une transaction.
     *
     * @param array<string,mixed>        $order
     * @param list<array<string,mixed>>  $items
     * @return array{id:int,invoice_number:string}
     */
    public static function createWithItems(array $order, array $items): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $invoiceNumber = self::nextInvoiceNumber();

            Database::run(
                'INSERT INTO commandes
                    (utilisateur_id, numero_facture, email, statut, total_centimes, code_reduction, remise_centimes, devise,
                     facturation_nom, facturation_ligne1, facturation_ligne2, facturation_ville, facturation_region,
                     facturation_code_postal, facturation_pays, paiement_marque, paiement_quatre_derniers, stripe_payment_intent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $order['user_id'], $invoiceNumber, $order['email'], $order['status'], $order['total_cents'],
                    $order['discount_code'] ?? null, $order['discount_cents'] ?? 0,
                    $order['currency'], $order['billing_name'], $order['billing_line1'], $order['billing_line2'],
                    $order['billing_city'], $order['billing_region'], $order['billing_postal_code'],
                    $order['billing_country'], $order['payment_brand'], $order['payment_last4'], $order['stripe_payment_intent'],
                ],
            );
            $orderId = Database::lastInsertId();

            foreach ($items as $item) {
                $lineTotal = (int) $item['unit_price_cents'] * (int) $item['quantity'];
                Database::run(
                    'INSERT INTO lignes_commande
                        (commande_id, produit_id, produit_nom, periodicite, quantite, prix_unitaire_centimes, total_ligne_centimes)
                     VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [
                        $orderId, $item['product_id'], $item['product_name'], $item['billing_period'],
                        $item['quantity'], $item['unit_price_cents'], $lineTotal,
                    ],
                );
            }

            $pdo->commit();

            return ['id' => $orderId, 'invoice_number' => $invoiceNumber];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM commandes WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null Commande appartenant à l'utilisateur donné. */
    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM commandes WHERE id = ? AND utilisateur_id = ?',
            [$id, $userId],
        );
    }

    /** @return array<string,mixed>|null */
    public static function findByInvoice(string $invoiceNumber): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM commandes WHERE numero_facture = ?', [$invoiceNumber]);
    }

    /** @return list<array<string,mixed>> */
    public static function items(int $orderId): array
    {
        return Database::fetchAll('SELECT ' . self::SELECT_ITEMS . ' FROM lignes_commande WHERE commande_id = ?', [$orderId]);
    }

    /**
     * Historique des commandes d'un utilisateur, filtrable par année.
     *
     * @return list<array<string,mixed>>
     */
    public static function forUser(int $userId, ?int $year = null, string $search = ''): array
    {
        $where = 'utilisateur_id = ?';
        $params = [$userId];

        if ($year !== null) {
            $where .= ' AND YEAR(cree_le) = ?';
            $params[] = $year;
        }
        if ($search !== '') {
            $where .= ' AND numero_facture LIKE ?';
            $params[] = '%' . $search . '%';
        }

        return Database::fetchAll(
            'SELECT ' . self::SELECT . " FROM commandes WHERE {$where} ORDER BY cree_le DESC",
            $params,
        );
    }

    /** @return list<int> Années distinctes de commande d'un utilisateur. */
    public static function yearsForUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT DISTINCT YEAR(cree_le) AS y FROM commandes WHERE utilisateur_id = ? ORDER BY y DESC',
            [$userId],
        );

        return array_map(static fn (array $r): int => (int) $r['y'], $rows);
    }

    /**
     * Liste paginée pour le back-office.
     *
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function paginate(int $offset, int $perPage, string $status = ''): array
    {
        $where = '';
        $params = [];
        if ($status !== '') {
            $where = ' WHERE statut = ?';
            $params[] = $status;
        }

        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM commandes' . $where, $params);
        $items = Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM commandes' . $where . ' ORDER BY cree_le DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params,
        );

        return ['items' => $items, 'total' => $total];
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::run('UPDATE commandes SET statut = ? WHERE id = ?', [$status, $id]);
    }

    /** Enregistre l'URL de la facture PDF générée par Stripe. */
    public static function setInvoiceUrl(int $id, string $url): void
    {
        Database::run('UPDATE commandes SET stripe_invoice_url = ? WHERE id = ?', [$url, $id]);
    }

    /**
     * Toutes les commandes (optionnellement filtrées par statut) pour l'export.
     *
     * @return list<array<string,mixed>>
     */
    public static function allForExport(string $status = ''): array
    {
        $where = '';
        $params = [];
        if ($status !== '') {
            $where = ' WHERE statut = ?';
            $params[] = $status;
        }

        return Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM commandes' . $where . ' ORDER BY cree_le DESC',
            $params,
        );
    }

    // --------------------------------------------------- Statistiques (dashboard)

    /**
     * Chiffre d'affaires par jour sur N jours.
     *
     * @return list<array{label:string,total:int}>
     */
    public static function salesByDay(int $days): array
    {
        $placeholders = implode(',', array_fill(0, count(self::SALE_STATUSES), '?'));
        $rows = Database::fetchAll(
            "SELECT DATE(cree_le) AS label, SUM(total_centimes) AS total
             FROM commandes
             WHERE statut IN ({$placeholders}) AND cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(cree_le) ORDER BY label",
            [...self::SALE_STATUSES, $days],
        );

        return array_map(static fn (array $r): array => [
            'label' => (string) $r['label'],
            'total' => (int) $r['total'],
        ], $rows);
    }

    /**
     * Répartition du chiffre d'affaires par catégorie sur N jours.
     *
     * @return list<array{label:string,total:int}>
     */
    public static function salesByCategory(int $days): array
    {
        $placeholders = implode(',', array_fill(0, count(self::SALE_STATUSES), '?'));
        $rows = Database::fetchAll(
            "SELECT c.nom AS label, SUM(oi.total_ligne_centimes) AS total
             FROM lignes_commande oi
             JOIN commandes o ON o.id = oi.commande_id
             JOIN produits p ON p.id = oi.produit_id
             JOIN categories c ON c.id = p.categorie_id
             WHERE o.statut IN ({$placeholders}) AND o.cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY c.id ORDER BY total DESC",
            [...self::SALE_STATUSES, $days],
        );

        return array_map(static fn (array $r): array => [
            'label' => (string) $r['label'],
            'total' => (int) $r['total'],
        ], $rows);
    }

    /** Indicateurs synthétiques (cartes du tableau de bord). @return array<string,int> */
    public static function summary(int $days): array
    {
        $placeholders = implode(',', array_fill(0, count(self::SALE_STATUSES), '?'));
        $params = [...self::SALE_STATUSES, $days];

        $revenue = (int) Database::fetchValue(
            "SELECT COALESCE(SUM(total_centimes), 0) FROM commandes
             WHERE statut IN ({$placeholders}) AND cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            $params,
        );
        $count = (int) Database::fetchValue(
            "SELECT COUNT(*) FROM commandes
             WHERE statut IN ({$placeholders}) AND cree_le >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
            $params,
        );

        return [
            'revenue'    => $revenue,
            'orders'     => $count,
            'avg_basket' => $count > 0 ? (int) round($revenue / $count) : 0,
        ];
    }

    private static function nextInvoiceNumber(): string
    {
        $year = (int) date('Y');
        $count = (int) Database::fetchValue(
            'SELECT COUNT(*) FROM commandes WHERE YEAR(cree_le) = ?',
            [$year],
        );

        return sprintf('CYNA-%d-%06d', $year, $count + 1);
    }
}
