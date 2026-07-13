<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Accès aux codes de réduction (promotions).
 *
 * Base en français : colonnes aliasées vers les clés applicatives.
 */
final class PromotionRepository
{
    private const SELECT = 'id, code, titre AS title, type, valeur AS value, actif AS active,
        expire_le AS expires_at, utilisations_max AS max_uses, utilisations AS uses, cree_le AS created_at';

    /** @return array<string,mixed>|null */
    public static function findByCode(string $code): ?array
    {
        return Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM codes_reduction WHERE code = ?',
            [strtoupper(trim($code))],
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM codes_reduction WHERE id = ?', [$id]);
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        return Database::fetchAll('SELECT ' . self::SELECT . ' FROM codes_reduction ORDER BY cree_le DESC');
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): void
    {
        Database::run(
            'INSERT INTO codes_reduction (code, titre, type, valeur, actif, expire_le, utilisations_max)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                strtoupper(trim((string) $data['code'])),
                ($data['title'] ?? '') !== '' ? $data['title'] : null,
                $data['type'],
                (int) $data['value'],
                (int) ($data['active'] ?? 1),
                $data['expires_at'] ?: null,
                $data['max_uses'] !== '' && $data['max_uses'] !== null ? (int) $data['max_uses'] : null,
            ],
        );
    }

    public static function toggle(int $id): void
    {
        Database::run('UPDATE codes_reduction SET actif = 1 - actif WHERE id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM codes_reduction WHERE id = ?', [$id]);
    }

    /** Incrémente le compteur d'utilisations après une commande réussie. */
    public static function incrementUsage(string $code): void
    {
        Database::run(
            'UPDATE codes_reduction SET utilisations = utilisations + 1 WHERE code = ?',
            [strtoupper(trim($code))],
        );
    }
}
