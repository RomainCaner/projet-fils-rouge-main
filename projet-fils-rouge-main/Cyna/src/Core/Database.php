<?php

declare(strict_types=1);

namespace Cyna\Core;

use PDO;
use PDOStatement;

/**
 * Point d'accès unique à la base MySQL via PDO.
 *
 * Toutes les requêtes passent par des requêtes préparées (paramètres liés)
 * afin d'éliminer tout risque d'injection SQL, conformément au plan de
 * sécurité de la note de cadrage.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::get('db.host'),
            Config::get('db.port'),
            Config::get('db.name'),
        );

        self::$pdo = new PDO($dsn, Config::get('db.user'), Config::get('db.password'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    /**
     * Exécute une requête préparée et renvoie le statement.
     *
     * @param array<string,mixed>|list<mixed> $params
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Récupère une seule ligne (ou null).
     *
     * @param array<string,mixed>|list<mixed> $params
     * @return array<string,mixed>|null
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Récupère toutes les lignes.
     *
     * @param array<string,mixed>|list<mixed> $params
     * @return list<array<string,mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * Récupère une valeur scalaire (première colonne de la première ligne).
     *
     * @param array<string,mixed>|list<mixed> $params
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        return self::run($sql, $params)->fetchColumn();
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }
}
