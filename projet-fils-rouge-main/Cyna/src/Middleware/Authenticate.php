<?php

declare(strict_types=1);

namespace Cyna\Middleware;

use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Services\Auth;

/**
 * Protège les pages privées du front-office.
 *
 * Un visiteur non connecté est redirigé vers la connexion ; l'URL demandée
 * est mémorisée afin de l'y renvoyer après authentification.
 */
final class Authenticate
{
    public function handle(Request $request): ?Response
    {
        if (!Auth::check()) {
            Session::set('intended_url', $request->path);
            Session::flash('error', t('auth.login_required'));

            return Response::redirect(url('/connexion'));
        }

        return null;
    }
}
