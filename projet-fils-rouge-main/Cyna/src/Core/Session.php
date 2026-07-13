<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Gestion de la session PHP avec réglages sécurisés.
 *
 * Cookies httponly + samesite + secure (sur HTTPS), régénération de l'ID
 * après authentification, et helper de messages "flash".
 */
final class Session
{
    public static function start(bool $secure): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax',
        ]);
        session_name('cyna_session');
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Régénère l'identifiant de session (à appeler après login). */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /** Dépose un message flash affiché une seule fois (à la requête suivante). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Mémorise les saisies de formulaire pour les réafficher après une erreur
     * de validation (les champs sensibles sont systématiquement exclus).
     *
     * @param array<string,mixed> $input
     */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['current_password'], $input['_token']);
        $_SESSION['_old'] = $input;
    }
}
