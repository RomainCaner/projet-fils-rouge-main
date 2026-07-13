<?php

declare(strict_types=1);

/**
 * Amorçage minimal de l'environnement de test.
 *
 * Contrairement à bootstrap/app.php (qui démarre la session, l'i18n et le
 * routeur), ce fichier se limite à ce dont les tests unitaires ont besoin :
 *   - la constante BASE_PATH ;
 *   - l'autoloader PSR-4 « Cyna\ » => src/ (identique à celui de production) ;
 *   - les fonctions utilitaires globales (helpers).
 *
 * Aucune connexion à la base de données ni session PHP réelle n'est requise :
 * les tests manipulent directement le superglobal $_SESSION.
 */

define('BASE_PATH', dirname(__DIR__));

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

// Un superglobal de session vide et prévisible pour chaque exécution.
$_SESSION = [];
