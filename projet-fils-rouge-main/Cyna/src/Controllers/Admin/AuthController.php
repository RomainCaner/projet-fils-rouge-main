<?php

declare(strict_types=1);

namespace Cyna\Controllers\Admin;

use Cyna\Core\Config;
use Cyna\Core\Controller;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Totp;
use Cyna\Models\User;
use Cyna\Services\Auth;

/**
 * Authentification du back-office en deux étapes :
 *   1. e-mail + mot de passe (compte administrateur) ;
 *   2. code TOTP (2FA). Au premier accès, l'administrateur enrôle son
 *      application d'authentification (Google Authenticator, Authy, ...).
 */
final class AuthController extends Controller
{
    private const ENROLL_KEY = 'totp_enroll_secret';

    public function showLogin(Request $request): Response
    {
        if (Auth::isAdmin()) {
            return $this->redirect('/admin');
        }

        return $this->view('admin/auth/login', ['title' => 'Connexion administrateur']);
    }

    public function login(Request $request): Response
    {
        $user = User::findByEmail($request->string('email'));

        if ($user === null || !$user->isAdmin() || !$user->verifyPassword($request->string('password'))) {
            return $this->back(['email' => ['Identifiants administrateur invalides.']], ['email' => $request->string('email')]);
        }

        Auth::setAdminPending($user);

        return $this->redirect('/admin/2fa');
    }

    public function showTwoFactor(Request $request): Response
    {
        $user = Auth::adminPending();
        if ($user === null) {
            return $this->redirect('/admin/connexion');
        }

        // Premier accès : génération d'un secret à enrôler.
        $enrolling = !$user->totpEnabled;
        $secret = null;
        $uri = null;
        if ($enrolling) {
            $secret = (string) Session::get(self::ENROLL_KEY);
            if ($secret === '') {
                $secret = Totp::generateSecret();
                Session::set(self::ENROLL_KEY, $secret);
            }
            $uri = Totp::provisioningUri($secret, $user->email, (string) Config::get('security.totp_issuer', 'Cyna'));
        }

        return $this->view('admin/auth/two_factor', [
            'title'     => 'Double authentification',
            'enrolling' => $enrolling,
            'secret'    => $secret,
            'uri'       => $uri,
        ]);
    }

    public function verifyTwoFactor(Request $request): Response
    {
        $user = Auth::adminPending();
        if ($user === null) {
            return $this->redirect('/admin/connexion');
        }

        $enrolling = !$user->totpEnabled;
        $secret = $enrolling ? (string) Session::get(self::ENROLL_KEY) : (string) $user->totpSecret;

        if (!Totp::verify($secret, $request->string('code'))) {
            return $this->back(['code' => ['Code invalide. Veuillez réessayer.']]);
        }

        if ($enrolling) {
            $user->enableTotp($secret);
            Session::forget(self::ENROLL_KEY);
        }

        Auth::completeAdminLogin($user);
        $intended = (string) Session::get('intended_url', '/admin');
        Session::forget('intended_url');

        return $this->redirect($intended);
    }

    public function logout(Request $request): Response
    {
        Auth::logoutAdmin();

        return $this->redirect('/admin/connexion');
    }
}
