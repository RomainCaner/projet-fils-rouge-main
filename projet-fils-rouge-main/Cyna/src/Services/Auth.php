<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Core\Session;
use Cyna\Models\User;

/**
 * Service d'authentification.
 *
 * Deux contextes distincts coexistent :
 *  - le client du front-office (clé de session « user_id ») ;
 *  - l'administrateur du back-office (clé « admin_id »), qui n'est validé
 *    qu'après l'étape de double authentification (2FA).
 *
 * La fonctionnalité « Se souvenir de moi » s'appuie sur un jeton aléatoire
 * stocké haché en base et déposé dans un cookie.
 */
final class Auth
{
    private const REMEMBER_COOKIE = 'cyna_remember';

    private static ?User $user = null;
    private static bool $resolved = false;

    // ----------------------------------------------------------------- Client

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || !$user->verifyPassword($password)) {
            return false;
        }
        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        self::login($user);

        return true;
    }

    public static function login(User $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('user_id', $user->id);
        self::$user = $user;
        self::$resolved = true;

        if ($remember) {
            self::rememberUser($user);
        }
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()?->id;
    }

    public static function user(): ?User
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;

        $id = Session::get('user_id');
        if ($id !== null) {
            return self::$user = User::find((int) $id);
        }

        // Reconnexion automatique via le cookie « Se souvenir de moi ».
        $token = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (is_string($token) && $token !== '') {
            $user = User::findByRememberToken(hash('sha256', $token));
            if ($user !== null) {
                Session::set('user_id', $user->id);

                return self::$user = $user;
            }
        }

        return self::$user = null;
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user !== null) {
            $user->setRememberToken(null);
        }
        self::clearRememberCookie();
        Session::destroy();
        self::$user = null;
    }

    // ------------------------------------------------------------- Back-office

    /** Étape 1 réussie (mot de passe) : on mémorise l'admin en attente de 2FA. */
    public static function setAdminPending(User $user): void
    {
        Session::set('admin_pending_id', $user->id);
    }

    public static function adminPending(): ?User
    {
        $id = Session::get('admin_pending_id');

        return $id !== null ? User::find((int) $id) : null;
    }

    /** Étape 2 réussie (TOTP) : l'administrateur est pleinement authentifié. */
    public static function completeAdminLogin(User $user): void
    {
        Session::regenerate();
        Session::forget('admin_pending_id');
        Session::set('admin_id', $user->id);
    }

    public static function isAdmin(): bool
    {
        $id = Session::get('admin_id');
        if ($id === null) {
            return false;
        }
        $user = User::find((int) $id);

        return $user !== null && $user->isAdmin();
    }

    public static function adminUser(): ?User
    {
        $id = Session::get('admin_id');

        return $id !== null ? User::find((int) $id) : null;
    }

    public static function logoutAdmin(): void
    {
        Session::forget('admin_id');
        Session::forget('admin_pending_id');
    }

    // ----------------------------------------------------------------- Privé

    private static function rememberUser(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $user->setRememberToken(hash('sha256', $token));

        setcookie(self::REMEMBER_COOKIE, $token, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'httponly' => true,
            'secure'   => ($_SERVER['HTTPS'] ?? '') === 'on',
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
        }
    }
}
