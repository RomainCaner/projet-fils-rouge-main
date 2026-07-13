<?php

declare(strict_types=1);

/**
 * Point d'entrée unique de l'application (Front Controller).
 * Toutes les requêtes HTTP sont réécrites vers ce fichier (cf. .htaccess).
 */

[$kernel, $request] = require dirname(__DIR__) . '/bootstrap/app.php';

$kernel->handle($request)->send();
