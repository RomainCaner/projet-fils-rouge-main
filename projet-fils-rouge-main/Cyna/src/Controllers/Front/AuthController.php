<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Mailer;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Core\View;
use Cyna\Models\User;
use Cyna\Services\Auth;

/**
 * Authentification du front-office : inscription avec confirmation par e-mail,
 * connexion (option « se souvenir de moi »), déconnexion et réinitialisation
 * du mot de passe par lien sécurisé à durée de validité limitée.
 */
final class AuthController extends Controller
{
    public function showRegister(Request $request): Response
    {
        return $this->view('front/auth/register', ['title' => t('auth.register_title')]);
    }

    public function register(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|max:160',
            'email'     => 'required|email|max:190',
            'password'  => 'required|password|confirmed',
        ], [
            'full_name' => t('auth.full_name'),
            'email'     => t('auth.email'),
            'password'  => t('auth.password'),
        ]);

        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        if (User::findByEmail($request->string('email')) !== null) {
            return $this->back(['email' => [t('auth.email_taken')]], $request->all());
        }

        [, $token] = User::createCustomer(
            $request->string('full_name'),
            $request->string('email'),
            $request->string('password'),
        );

        $this->sendMail(
            $request->string('email'),
            t('auth.confirm_subject'),
            'emails/confirm',
            ['link' => url('/confirmation/' . $token)],
        );

        Session::flash('success', t('auth.register_done'));

        return $this->redirect('/connexion');
    }

    public function confirmEmail(Request $request, string $token): Response
    {
        $user = User::findByConfirmationToken($token);
        if ($user === null) {
            Session::flash('error', t('auth.confirm_invalid'));

            return $this->redirect('/connexion');
        }

        $user->markEmailVerified();
        Auth::login($user);
        Session::flash('success', t('auth.confirm_done'));

        return $this->redirect('/compte');
    }

    public function showLogin(Request $request): Response
    {
        return $this->view('front/auth/login', ['title' => t('auth.login_title')]);
    }

    public function login(Request $request): Response
    {
        $email = $request->string('email');
        $user = User::findByEmail($email);

        if ($user === null || !$user->verifyPassword($request->string('password'))) {
            return $this->back(['email' => [t('auth.login_invalid')]], ['email' => $email]);
        }
        if (!$user->hasVerifiedEmail()) {
            return $this->back(['email' => [t('auth.login_unverified')]], ['email' => $email]);
        }

        Auth::login($user, $request->boolean('remember'));

        // Redirection vers la page initialement demandée le cas échéant.
        $intended = (string) Session::get('intended_url', '/compte');
        Session::forget('intended_url');

        return $this->redirect($intended);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();

        return $this->redirect('/');
    }

    public function showForgot(Request $request): Response
    {
        return $this->view('front/auth/forgot', ['title' => t('auth.forgot_title')]);
    }

    public function sendReset(Request $request): Response
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email'], ['email' => t('auth.email')]);
        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        $user = User::findByEmail($request->string('email'));
        if ($user !== null) {
            $token = User::setResetToken($user->id);
            $this->sendMail(
                $user->email,
                t('auth.reset_subject'),
                'emails/reset',
                ['link' => url('/reinitialiser/' . $token)],
            );
        }

        // Message identique que le compte existe ou non (anti-énumération).
        Session::flash('success', t('auth.reset_sent'));

        return $this->redirect('/connexion');
    }

    public function showReset(Request $request, string $token): Response
    {
        if (User::findByResetToken($token) === null) {
            Session::flash('error', t('auth.reset_invalid'));

            return $this->redirect('/mot-de-passe-oublie');
        }

        return $this->view('front/auth/reset', ['title' => t('auth.reset_title'), 'token' => $token]);
    }

    public function resetPassword(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => t('auth.password')]);

        if ($validator->fails()) {
            return $this->back($validator->errors());
        }

        $user = User::findByResetToken($request->string('token'));
        if ($user === null) {
            Session::flash('error', t('auth.reset_invalid'));

            return $this->redirect('/mot-de-passe-oublie');
        }

        $user->resetPassword($request->string('password'));
        Session::flash('success', t('auth.reset_done'));

        return $this->redirect('/connexion');
    }

    /** Envoie un e-mail à partir d'un gabarit, sans interrompre l'UX en cas d'échec SMTP. */
    private function sendMail(string $to, string $subject, string $template, array $data): void
    {
        try {
            Mailer::send($to, $subject, (new View())->render($template, $data + ['subject' => $subject]));
        } catch (\Throwable $e) {
            error_log('Envoi e-mail échoué : ' . $e->getMessage());
        }
    }
}
