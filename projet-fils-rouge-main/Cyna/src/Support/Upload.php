<?php

declare(strict_types=1);

namespace Cyna\Support;

/**
 * Gestion sécurisée des téléversements d'images (back-office).
 *
 * Valide le type réel du fichier (et non l'extension annoncée) et la taille,
 * puis stocke l'image sous public/uploads/{dossier} avec un nom aléatoire.
 */
final class Upload
{
    private const MAX_BYTES = 3_145_728; // 3 Mo
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string,mixed> $file Entrée issue de $_FILES
     * @return string|null Chemin relatif (ex: "uploads/products/ab12.webp") ou null
     */
    public static function store(array $file, string $folder): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            return null;
        }

        $directory = BASE_PATH . '/public/uploads/' . trim($folder, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $name = bin2hex(random_bytes(8)) . '.' . self::ALLOWED[$mime];
        $relative = 'uploads/' . trim($folder, '/') . '/' . $name;

        if (!move_uploaded_file((string) $file['tmp_name'], BASE_PATH . '/public/' . $relative)) {
            return null;
        }

        return $relative;
    }
}
