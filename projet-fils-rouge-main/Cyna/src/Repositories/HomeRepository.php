<?php

declare(strict_types=1);

namespace Cyna\Repositories;

use Cyna\Core\Database;

/**
 * Contenu éditorial de la page d'accueil (carrousel et réglages clé/valeur),
 * administrable depuis le back-office. Base en français, colonnes aliasées.
 */
final class HomeRepository
{
    private const SELECT = 'id, titre AS title, sous_titre AS subtitle, image, lien_url AS link_url, position, active AS is_active';

    /** @return list<array<string,mixed>> Diapositives actives, dans l'ordre. */
    public static function activeSlides(): array
    {
        return Database::fetchAll('SELECT ' . self::SELECT . ' FROM diapositives_accueil WHERE active = 1 ORDER BY position');
    }

    /** @return list<array<string,mixed>> Toutes les diapositives (back-office). */
    public static function allSlides(): array
    {
        return Database::fetchAll('SELECT ' . self::SELECT . ' FROM diapositives_accueil ORDER BY position');
    }

    public static function createSlide(string $title, ?string $subtitle, ?string $image, ?string $link, int $position, bool $active): int
    {
        Database::run(
            'INSERT INTO diapositives_accueil (titre, sous_titre, image, lien_url, position, active) VALUES (?, ?, ?, ?, ?, ?)',
            [$title, $subtitle, $image, $link, $position, $active ? 1 : 0],
        );

        return Database::lastInsertId();
    }

    public static function updateSlide(int $id, string $title, ?string $subtitle, ?string $image, ?string $link, int $position, bool $active): void
    {
        Database::run(
            'UPDATE diapositives_accueil SET titre = ?, sous_titre = ?, image = COALESCE(?, image), lien_url = ?, position = ?, active = ? WHERE id = ?',
            [$title, $subtitle, $image, $link, $position, $active ? 1 : 0, $id],
        );
    }

    public static function deleteSlide(int $id): void
    {
        Database::run('DELETE FROM diapositives_accueil WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------------ Réglages

    public static function setting(string $key, string $default = ''): string
    {
        $value = Database::fetchValue('SELECT valeur FROM reglages WHERE cle = ?', [$key]);

        return $value !== false && $value !== null ? (string) $value : $default;
    }

    public static function setSetting(string $key, string $value): void
    {
        Database::run(
            'INSERT INTO reglages (cle, valeur) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            [$key, $value],
        );
    }
}
