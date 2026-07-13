<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Lecture des utilisateurs pour le back-office (liste paginée).
 * Base en français ; colonnes aliasées vers les clés applicatives.
 */
final class UserRepository
{
    private const SELECT = 'id, nom_complet AS full_name, email, role, email_verifie_le AS email_verified_at, cree_le AS created_at';

    /**
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function paginate(int $offset, int $perPage, string $search = ''): array
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE nom_complet LIKE ? OR email LIKE ?';
            $params = ['%' . $search . '%', '%' . $search . '%'];
        }

        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM utilisateurs' . $where, $params);
        $items = Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM utilisateurs' . $where .
            ' ORDER BY cree_le DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params,
        );

        return ['items' => $items, 'total' => $total];
    }
}
