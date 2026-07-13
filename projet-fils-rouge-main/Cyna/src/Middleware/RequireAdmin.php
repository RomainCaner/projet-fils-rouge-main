<?php

declare(strict_types=1);

namespace Cyna\Middleware;

use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Services\Auth;

/**
 * Restreint l'accès au back-office aux administrateurs pleinement authentifiés.
 *
 * Un administrateur n'est considéré authentifié qu'après avoir validé l'étape
 * de double authentification (2FA TOTP).
 */
final class RequireAdmin
{
    public function handle(Request $request): ?Response
    {
        if (!Auth::isAdmin()) {
            Session::set('intended_url', $request->path);

            return Response::redirect(url('/admin/connexion'));
        }

        return null;
    }
}
