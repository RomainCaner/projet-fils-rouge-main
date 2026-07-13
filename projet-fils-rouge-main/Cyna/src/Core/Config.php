<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Accès en lecture à la configuration applicative.
 *
 * La configuration est dérivée des variables d'environnement (cf. Env) afin
 * de centraliser tous les réglages en un seul point et d'éviter d'appeler
 * Env::get() partout dans le code.
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];

    public static function boot(): void
    {
        self::$items = [
            'app' => [
                'env'     => Env::get('APP_ENV', 'production'),
                'name'    => Env::get('APP_NAME', 'Cyna'),
                'url'     => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
                'key'     => (string) Env::get('APP_KEY', ''),
                'locale'  => Env::get('APP_LOCALE', 'fr'),
                'locales' => array_filter(array_map('trim', explode(',', (string) Env::get('APP_LOCALES', 'fr,en')))),
            ],
            'db' => [
                'host'     => Env::get('DB_HOST', '127.0.0.1'),
                'port'     => (int) Env::get('DB_PORT', '3306'),
                'name'     => Env::get('DB_NAME', 'cyna'),
                'user'     => Env::get('DB_USER', 'root'),
                'password' => Env::get('DB_PASSWORD', ''),
            ],
            'mail' => [
                'host'       => Env::get('MAIL_HOST', 'localhost'),
                'port'       => (int) Env::get('MAIL_PORT', '1025'),
                'username'   => Env::get('MAIL_USERNAME', ''),
                'password'   => Env::get('MAIL_PASSWORD', ''),
                'encryption' => Env::get('MAIL_ENCRYPTION', 'none'),
                'from_email' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@cyna-it.fr'),
                'from_name'  => Env::get('MAIL_FROM_NAME', 'Cyna'),
            ],
            'stripe' => [
                'secret'      => Env::get('STRIPE_SECRET_KEY', ''),
                'publishable' => Env::get('STRIPE_PUBLISHABLE_KEY', ''),
                'currency'    => Env::get('STRIPE_CURRENCY', 'eur'),
            ],
            'security' => [
                'totp_issuer' => Env::get('TOTP_ISSUER', 'Cyna'),
            ],
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function isProduction(): bool
    {
        return self::get('app.env') === 'production';
    }
}
