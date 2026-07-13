<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Internationalisation (i18n) côté serveur.
 *
 * Les traductions sont des tableaux PHP associatifs (resources/lang/{locale}.php).
 * La langue active est mémorisée en session. Le socle gère également le sens
 * de lecture (LTR/RTL) pour préparer l'arabe ou l'hébreu.
 */
final class Translator
{
    private static string $locale = 'fr';

    /** @var array<string,mixed> */
    private static array $messages = [];

    /** Langues s'écrivant de droite à gauche. */
    private const RTL_LOCALES = ['ar', 'he', 'fa', 'ur'];

    public static function boot(): void
    {
        $available = (array) Config::get('app.locales', ['fr']);
        $requested = (string) Session::get('locale', (string) Config::get('app.locale', 'fr'));
        self::$locale = in_array($requested, $available, true) ? $requested : (string) Config::get('app.locale', 'fr');

        $file = BASE_PATH . '/resources/lang/' . self::$locale . '.php';
        self::$messages = is_file($file) ? (array) require $file : [];
    }

    public static function setLocale(string $locale): void
    {
        if (in_array($locale, (array) Config::get('app.locales', ['fr']), true)) {
            Session::set('locale', $locale);
        }
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function isRtl(): bool
    {
        return in_array(self::$locale, self::RTL_LOCALES, true);
    }

    public static function direction(): string
    {
        return self::isRtl() ? 'rtl' : 'ltr';
    }

    /**
     * Traduit une clé en notation pointée (ex: "auth.login_title").
     * Les valeurs absentes renvoient la clé brute pour faciliter le repérage.
     *
     * @param array<string,string|int> $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $value = self::$messages;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $key;
            }
            $value = $value[$segment];
        }

        if (!is_string($value)) {
            return $key;
        }

        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }

        return $value;
    }
}
