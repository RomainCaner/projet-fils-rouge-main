<?php

declare(strict_types=1);

use Cyna\Core\Config;
use Cyna\Core\Csrf;
use Cyna\Core\Flash;
use Cyna\Core\Translator;
use Cyna\Repositories\CategoryRepository;
use Cyna\Services\Auth;
use Cyna\Services\Cart;

/**
 * Fonctions utilitaires globales disponibles dans les contrôleurs et gabarits.
 */

if (!function_exists('e')) {
    /** Échappe une valeur pour un affichage HTML sûr (protection XSS). */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('t')) {
    /** Traduit une clé i18n. @param array<string,string|int> $replace */
    function t(string $key, array $replace = []): string
    {
        return Translator::get($key, $replace);
    }
}

if (!function_exists('url')) {
    /** Construit une URL absolue à partir d'un chemin relatif. */
    function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return (string) Config::get('app.url') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** URL d'une ressource statique (CSS, JS, image). */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    /** Champ caché à inclure dans chaque formulaire mutateur. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
    }
}

if (!function_exists('old')) {
    /** Récupère une ancienne saisie de formulaire après une erreur. */
    function old(string $key, string $default = ''): string
    {
        return e((string) Flash::old($key, $default));
    }
}

if (!function_exists('errors')) {
    /**
     * Erreurs de validation de la requête précédente.
     *
     * @return array<string,list<string>>
     */
    function errors(): array
    {
        return Flash::errors();
    }
}

if (!function_exists('flash_messages')) {
    /**
     * Messages flash (success, error, info) à afficher une seule fois.
     *
     * @return array<string,list<string>>
     */
    function flash_messages(): array
    {
        return Flash::messages();
    }
}

if (!function_exists('error')) {
    /** Première erreur d'un champ donné, ou null. */
    function error(string $field): ?string
    {
        return errors()[$field][0] ?? null;
    }
}

if (!function_exists('auth_user')) {
    /** Utilisateur authentifié courant (ou null). */
    function auth_user(): ?\Cyna\Models\User
    {
        return Auth::user();
    }
}

if (!function_exists('cart_count')) {
    /** Nombre d'articles dans le panier (pour l'indicateur d'en-tête). */
    function cart_count(): int
    {
        return Cart::count();
    }
}

if (!function_exists('money')) {
    /** Met en forme un montant stocké en centimes (ex: 4990 => "49,90 €"). */
    function money(int $cents): string
    {
        $amount = number_format($cents / 100, 2, ',', ' ');

        return $amount . ' €';
    }
}

if (!function_exists('image_url')) {
    /**
     * URL d'une image stockée (uploads ou assets/img), avec repli sur un
     * visuel par défaut lorsque le fichier est absent — évite les images
     * cassées sans recourir à du JavaScript inline (politique CSP stricte).
     */
    function image_url(?string $path): string
    {
        if ($path !== null && $path !== '') {
            $candidates = [BASE_PATH . '/public/' . $path, BASE_PATH . '/public/assets/img/' . $path];
            foreach ([$path, 'assets/img/' . $path] as $i => $relative) {
                if (is_file($candidates[$i])) {
                    return url($relative);
                }
            }
        }

        return asset('img/placeholder.svg');
    }
}

if (!function_exists('slugify')) {
    /** Transforme un texte en identifiant d'URL (slug). */
    function slugify(string $value): string
    {
        $value = @iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
        $value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');

        return trim($value, '-');
    }
}

if (!function_exists('locale')) {
    function locale(): string
    {
        return Translator::locale();
    }
}

if (!function_exists('dir_attr')) {
    /** Sens de lecture courant (ltr/rtl) pour l'attribut HTML dir. */
    function dir_attr(): string
    {
        return Translator::direction();
    }
}

if (!function_exists('nav_categories')) {
    /**
     * Catégories de services pour le menu de navigation (SOC, EDR, XDR…).
     * Résultat mémorisé le temps de la requête pour ne charger la base qu'une fois.
     *
     * @return list<array<string,mixed>>
     */
    function nav_categories(): array
    {
        static $categories = null;

        if ($categories === null) {
            $categories = CategoryRepository::all();
        }

        return $categories;
    }
}
