<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Messages issus du formulaire de contact, consultables au back-office.
 * Base en français ; colonnes aliasées vers les clés applicatives.
 */
final class ContactRepository
{
    private const SELECT = 'id, email, sujet AS subject, corps AS body, statut AS status, cree_le AS created_at';

    public static function create(string $email, string $subject, string $body): int
    {
        Database::run(
            'INSERT INTO messages_contact (email, sujet, corps) VALUES (?, ?, ?)',
            [$email, $subject, $body],
        );

        return Database::lastInsertId();
    }

    /**
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public static function paginate(int $offset, int $perPage): array
    {
        $total = (int) Database::fetchValue('SELECT COUNT(*) FROM messages_contact');
        $items = Database::fetchAll(
            'SELECT ' . self::SELECT . ' FROM messages_contact ORDER BY cree_le DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
        );

        return ['items' => $items, 'total' => $total];
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Database::fetchOne('SELECT ' . self::SELECT . ' FROM messages_contact WHERE id = ?', [$id]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::run('UPDATE messages_contact SET statut = ? WHERE id = ?', [$status, $id]);
    }

    public static function countNew(): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM messages_contact WHERE statut = "new"');
    }
}
