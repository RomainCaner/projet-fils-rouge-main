<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Accès aux services SaaS (produits).
 *
 * Base en français : les colonnes sont aliasées vers les clés applicatives.
 * Le tri du catalogue applique la règle métier : services disponibles d'abord,
 * puis par priorité décroissante (back-office) ; les services en maintenance
 * sont relégués en fin de liste.
 */
final class ProductRepository
{
    /** Clause de tri du catalogue (disponibilité puis priorité). */
    private const CATALOG_ORDER = " ORDER BY (disponibilite = 'available') DESC, priorite DESC, nom ASC";

    /** Correspondance clé applicative → colonne SQL pour le tri back-office (anti-injection). */
    private const SORT_MAP = [
        'name'                => 'nom',
        'price_monthly_cents' => 'prix_mensuel_centimes',
        'priority'            => 'priorite',
        'availability'        => 'disponibilite',
        'created_at'          => 'cree_le',
    ];

    /** Liste des colonnes produit aliasées, éventuellement préfixées (ex: "p."). */
    private static function columns(string $p = ''): string
    {
        return "{$p}id, {$p}categorie_id AS category_id, {$p}slug, {$p}nom AS name,
            {$p}description_courte AS short_description, {$p}description, {$p}specifications AS tech_specs,
            {$p}prix_mensuel_centimes AS price_monthly_cents, {$p}prix_annuel_centimes AS price_annual_cents,
            {$p}disponibilite AS availability, {$p}priorite AS priority, {$p}est_mis_en_avant AS is_featured,
            {$p}position_mise_en_avant AS featured_position, {$p}image, {$p}cree_le AS created_at";
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT ' . self::columns() . ' FROM produits WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null Fiche produit avec sa catégorie. */
    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne(
            'SELECT ' . self::columns('p.') . ', c.nom AS category_name, c.slug AS category_slug
             FROM produits p JOIN categories c ON c.id = p.categorie_id
             WHERE p.slug = ?',
            [$slug],
        );
    }

    /** @return list<array<string,mixed>> Top produits mis en avant sur l'accueil. */
    public static function featured(int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT ' . self::columns() . " FROM produits WHERE est_mis_en_avant = 1
             ORDER BY (disponibilite = 'available') DESC, position_mise_en_avant ASC" . self::limit($limit),
        );
    }

    /**
     * Catalogue d'une catégorie, paginé.
     *
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function byCategory(int $categoryId, int $offset, int $perPage): array
    {
        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM produits WHERE categorie_id = ?', [$categoryId]);
        $items = Database::fetchAll(
            'SELECT ' . self::columns() . ' FROM produits WHERE categorie_id = ?' . self::CATALOG_ORDER . self::limit($perPage, $offset),
            [$categoryId],
        );

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Services similaires (même catégorie), tirés aléatoirement, disponibles
     * en priorité.
     *
     * @return list<array<string,mixed>>
     */
    public static function similar(int $productId, int $categoryId, int $limit = 6): array
    {
        return Database::fetchAll(
            'SELECT ' . self::columns() . " FROM produits
             WHERE categorie_id = ? AND id <> ?
             ORDER BY (disponibilite = 'available') DESC, RAND()" . self::limit($limit),
            [$categoryId, $productId],
        );
    }

    /** @return list<array<string,mixed>> */
    public static function images(int $productId): array
    {
        return Database::fetchAll(
            'SELECT id, produit_id AS product_id, chemin AS path, alt, position
             FROM images_produit WHERE produit_id = ? ORDER BY position',
            [$productId],
        );
    }

    /**
     * Candidats pour la recherche avancée (filtres structurés). Le classement
     * par pertinence et le tri final sont réalisés par SearchService.
     *
     * @param array{categories?:list<int>,price_min?:int,price_max?:int,available_only?:bool} $filters
     * @return list<array<string,mixed>>
     */
    public static function searchCandidates(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $where[] = "p.categorie_id IN ({$placeholders})";
            $params = array_merge($params, $filters['categories']);
        }
        if (isset($filters['price_min'])) {
            $where[] = 'p.prix_mensuel_centimes >= ?';
            $params[] = $filters['price_min'];
        }
        if (isset($filters['price_max'])) {
            $where[] = 'p.prix_mensuel_centimes <= ?';
            $params[] = $filters['price_max'];
        }
        if (!empty($filters['available_only'])) {
            $where[] = "p.disponibilite = 'available'";
        }

        return Database::fetchAll(
            'SELECT ' . self::columns('p.') . ', c.nom AS category_name FROM produits p
             JOIN categories c ON c.id = p.categorie_id
             WHERE ' . implode(' AND ', $where),
            $params,
        );
    }

    /**
     * Liste paginée pour le back-office, triable par colonne.
     *
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function paginateForAdmin(int $offset, int $perPage, string $sort, string $direction, string $search): array
    {
        $column = self::SORT_MAP[$sort] ?? 'nom';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE p.nom LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM produits p' . $where, $params);
        $items = Database::fetchAll(
            'SELECT ' . self::columns('p.') . ', c.nom AS category_name FROM produits p
             JOIN categories c ON c.id = p.categorie_id' . $where .
            " ORDER BY p.{$column} {$direction}" . self::limit($perPage, $offset),
            $params,
        );

        return ['items' => $items, 'total' => $total];
    }

    /** @param array<string,mixed> $data Clés applicatives (category_id, name, ...). */
    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO produits
                (categorie_id, slug, nom, description_courte, description, specifications,
                 prix_mensuel_centimes, prix_annuel_centimes, disponibilite, priorite,
                 est_mis_en_avant, position_mise_en_avant, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['category_id'], $data['slug'], $data['name'], $data['short_description'],
                $data['description'], $data['tech_specs'], $data['price_monthly_cents'],
                $data['price_annual_cents'], $data['availability'], $data['priority'],
                $data['is_featured'], $data['featured_position'], $data['image'],
            ],
        );

        return Database::lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        Database::run(
            'UPDATE produits SET
                categorie_id = ?, slug = ?, nom = ?, description_courte = ?, description = ?,
                specifications = ?, prix_mensuel_centimes = ?, prix_annuel_centimes = ?, disponibilite = ?,
                priorite = ?, est_mis_en_avant = ?, position_mise_en_avant = ?, image = COALESCE(?, image)
             WHERE id = ?',
            [
                $data['category_id'], $data['slug'], $data['name'], $data['short_description'],
                $data['description'], $data['tech_specs'], $data['price_monthly_cents'],
                $data['price_annual_cents'], $data['availability'], $data['priority'],
                $data['is_featured'], $data['featured_position'], $data['image'], $id,
            ],
        );
    }

    /** @param list<int> $ids Suppression multiple (action groupée du back-office). */
    public static function deleteMany(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::run("DELETE FROM produits WHERE id IN ({$placeholders})", $ids);
    }

    /** Construit une clause LIMIT/OFFSET sûre (valeurs entières forcées). */
    private static function limit(int $limit, ?int $offset = null): string
    {
        $clause = ' LIMIT ' . max(0, $limit);
        if ($offset !== null) {
            $clause .= ' OFFSET ' . max(0, $offset);
        }

        return $clause;
    }
}
