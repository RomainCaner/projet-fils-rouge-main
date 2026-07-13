<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Données « flash » à durée de vie d'une seule requête.
 *
 * Au démarrage, on extrait de la session les messages, erreurs de validation
 * et anciennes saisies déposés par la requête PRÉCÉDENTE, puis on les retire
 * de la session. Les écritures (Session::flash, Controller::back) ciblent donc
 * toujours la requête SUIVANTE.
 */
final class Flash
{
    /** @var array<string,list<string>> */
    private static array $messages = [];

    /** @var array<string,list<string>> */
    private static array $errors = [];

    /** @var array<string,mixed> */
    private static array $old = [];

    public static function boot(): void
    {
        self::$messages = (array) Session::get('_flash', []);
        self::$errors = (array) Session::get('_errors', []);
        self::$old = (array) Session::get('_old', []);

        Session::forget('_flash');
        Session::forget('_errors');
        Session::forget('_old');
    }

    /** @return array<string,list<string>> */
    public static function messages(): array
    {
        return self::$messages;
    }

    /** @return array<string,list<string>> */
    public static function errors(): array
    {
        return self::$errors;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        return self::$old[$key] ?? $default;
    }
}
