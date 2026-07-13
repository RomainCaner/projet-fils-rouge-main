<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Protection CSRF par jeton unique stocké en session.
 *
 * Chaque formulaire mutateur inclut le champ caché `_token` (voir helper
 * csrf_field()) qui est vérifié côté serveur via hash_equals().
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }

        return (string) Session::get(self::KEY);
    }

    public static function isValid(?string $token): bool
    {
        return is_string($token)
            && Session::has(self::KEY)
            && hash_equals((string) Session::get(self::KEY), $token);
    }
}
