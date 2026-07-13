<?php

declare(strict_types=1);

use Cyna\Core\Config;
use Cyna\Core\Env;
use Cyna\Core\Flash;
use Cyna\Core\Kernel;
use Cyna\Core\Request;
use Cyna\Core\Router;
use Cyna\Core\Session;
use Cyna\Core\Translator;

/**
 * Amorçage de l'application : autoloader, environnement, session, i18n, routes.
 * Retourne un Kernel prêt à traiter la requête.
 */

define('BASE_PATH', dirname(__DIR__));

/*
 * Autoloader PSR-4 maison (aucune dépendance externe / Composer).
 * Le préfixe « Cyna\ » est mappé sur le dossier « src/ ».
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Cyna\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/Support/helpers.php';

// Chargement de la configuration d'environnement.
Env::load(BASE_PATH . '/.env');
Config::boot();

// Gestion des erreurs selon l'environnement.
$isProduction = Config::isProduction();
error_reporting(E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');
date_default_timezone_set('Europe/Paris');

// Démarrage de la session (cookie sécurisé sur HTTPS) et de l'i18n.
$request = Request::capture();
Session::start($request->isSecure());
Flash::boot();
Translator::boot();

// Définition des routes.
$router = new Router();
(require BASE_PATH . '/routes/web.php')($router);
(require BASE_PATH . '/routes/admin.php')($router);

return [new Kernel($router), $request];
