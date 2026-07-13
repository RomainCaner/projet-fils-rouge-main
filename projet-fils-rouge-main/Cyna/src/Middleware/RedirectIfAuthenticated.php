<?php

declare(strict_types=1);

namespace Cyna\Middleware;

use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Services\Auth;

/**
 * Empêche un utilisateur déjà connecté d'accéder aux pages d'authentification
 * (connexion, inscription) en le renvoyant vers son espace compte.
 */
final class RedirectIfAuthenticated
{
    public function handle(Request $request): ?Response
    {
        if (Auth::check()) {
            return Response::redirect(url('/compte'));
        }

        return null;
    }
}
