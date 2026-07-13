<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Accès aux catégories de services.
 *
 * Base en français : les colonnes sont aliasées vers les clés applicatives
 * (ex: `nom AS name`) pour ne pas impacter les vues et contrôleurs.
 */
final class CategoryRepository
{
    private const SELECT = 'id, slug, nom AS name, description, image, position';

    /** @return list<array<string,mixed>> Catégories triées par position (back-office). */
    public static function all(): array
    {
        return Database::fetchAll('SELECT ' . self::SELECT . ' FROM categories ORDER BY position, nom');
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM categories WHERE id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM categories WHERE slug = ?', [$slug]);
    }

    /** @return list<array<string,mixed>> Catégories avec le nombre de services associés. */
    public static function allWithCounts(): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.slug, c.nom AS name, c.description, c.image, c.position, COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN produits p ON p.categorie_id = c.id
             GROUP BY c.id
             ORDER BY c.position, c.nom',
        );
    }

    public static function create(string $name, string $slug, string $description, ?string $image, int $position): int
    {
        Database::run(
            'INSERT INTO categories (nom, slug, description, image, position) VALUES (?, ?, ?, ?, ?)',
            [$name, $slug, $description, $image, $position],
        );

        return Database::lastInsertId();
    }

    public static function update(int $id, string $name, string $slug, string $description, ?string $image, int $position): void
    {
        Database::run(
            'UPDATE categories SET nom = ?, slug = ?, description = ?, image = COALESCE(?, image), position = ? WHERE id = ?',
            [$name, $slug, $description, $image, $position, $id],
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM categories WHERE id = ?', [$id]);
    }
}
