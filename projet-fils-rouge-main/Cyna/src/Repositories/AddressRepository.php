<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Carnet d'adresses des utilisateurs (facturation).
 *
 * Base en français ; colonnes aliasées vers les clés applicatives.
 */
final class AddressRepository
{
    private const SELECT = 'id, utilisateur_id AS user_id, prenom AS first_name, nom AS last_name,
        ligne1 AS line1, ligne2 AS line2, ville AS city, region, code_postal AS postal_code,
        pays AS country, telephone AS phone, par_defaut AS is_default';

    /** @return list<array<string,mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM adresses WHERE utilisateur_id = ? ORDER BY par_defaut DESC, id DESC',
            [$userId],
        );
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $userId): ?array
    {
        return Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM adresses WHERE id = ? AND utilisateur_id = ?',
            [$id, $userId],
        );
    }

    /** @param array<string,mixed> $data */
    public static function create(int $userId, array $data): int
    {
        Database::run(
            'INSERT INTO adresses (utilisateur_id, prenom, nom, ligne1, ligne2, ville, region, code_postal, pays, telephone)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId, $data['first_name'], $data['last_name'], $data['line1'], $data['line2'] ?: null,
                $data['city'], $data['region'] ?: null, $data['postal_code'], $data['country'], $data['phone'] ?: null,
            ],
        );

        return Database::lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, int $userId, array $data): void
    {
        Database::run(
            'UPDATE adresses SET prenom = ?, nom = ?, ligne1 = ?, ligne2 = ?, ville = ?, region = ?,
                code_postal = ?, pays = ?, telephone = ? WHERE id = ? AND utilisateur_id = ?',
            [
                $data['first_name'], $data['last_name'], $data['line1'], $data['line2'] ?: null, $data['city'],
                $data['region'] ?: null, $data['postal_code'], $data['country'], $data['phone'] ?: null, $id, $userId,
            ],
        );
    }

    public static function delete(int $id, int $userId): void
    {
        Database::run('DELETE FROM adresses WHERE id = ? AND utilisateur_id = ?', [$id, $userId]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        Database::run('UPDATE adresses SET par_defaut = 0 WHERE utilisateur_id = ?', [$userId]);
        Database::run('UPDATE adresses SET par_defaut = 1 WHERE id = ? AND utilisateur_id = ?', [$id, $userId]);
    }
}
