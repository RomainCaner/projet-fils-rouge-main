<?php

declare(strict_types=1);

namespace Cyna\Middleware;

use Cyna\Core\Csrf;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Request;
use Cyna\Core\Response;

/**
 * Vérifie le jeton anti-CSRF sur toutes les requêtes mutatrices (POST).
 */
final class VerifyCsrf
{
    public function handle(Request $request): ?Response
    {
        if ($request->isPost() && !Csrf::isValid($request->string('_token'))) {
            throw new HttpException(419, 'Session expirée ou jeton de sécurité invalide. Veuillez réessayer.');
        }

        return null;
    }
}
