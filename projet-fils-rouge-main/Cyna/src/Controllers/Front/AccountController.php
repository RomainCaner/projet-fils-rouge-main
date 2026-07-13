<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Models\User;
use Cyna\Repositories\AddressRepository;
use Cyna\Repositories\OrderRepository;
use Cyna\Repositories\SubscriptionRepository;
use Cyna\Services\Auth;
use Cyna\Services\InvoicePdf;

/**
 * Espace client : tableau de bord, paramètres du compte, carnet d'adresses,
 * abonnements et historique des commandes (factures PDF).
 *
 * Toutes les actions s'appuient sur l'utilisateur authentifié (middleware
 * Authenticate) et vérifient systématiquement la propriété des ressources.
 */
final class AccountController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $this->user();

        return $this->view('front/account/dashboard', [
            'title'         => t('account.title'),
            'user'          => $user,
            'subscriptions' => SubscriptionRepository::forUser($user->id),
            'recentOrders'  => array_slice(OrderRepository::forUser($user->id), 0, 5),
        ]);
    }

    // ----------------------------------------------------------- Paramètres

    public function settings(Request $request): Response
    {
        return $this->view('front/account/settings', ['title' => t('account.settings'), 'user' => $this->user()]);
    }

    public function updateProfile(Request $request): Response
    {
        $user = $this->user();
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|max:160',
            'email'     => 'required|email|max:190',
        ], ['full_name' => t('auth.full_name'), 'email' => t('auth.email')]);

        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        $user->updateProfile($request->string('full_name'));

        // Le changement d'e-mail est une opération sensible : mot de passe exigé.
        $newEmail = $request->string('email');
        if ($newEmail !== $user->email) {
            if (!$user->verifyPassword($request->string('current_password'))) {
                return $this->back(['current_password' => [t('account.password_required')]], $request->all());
            }
            if (User::findByEmail($newEmail) !== null) {
                return $this->back(['email' => [t('auth.email_taken')]], $request->all());
            }
            $user->updateEmail($newEmail);
        }

        Session::flash('success', t('account.profile_updated'));

        return $this->redirect('/compte/parametres');
    }

    public function updatePassword(Request $request): Response
    {
        $user = $this->user();
        if (!$user->verifyPassword($request->string('current_password'))) {
            return $this->back(['current_password' => [t('account.password_wrong')]]);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|password|confirmed',
        ], ['password' => t('auth.password')]);

        if ($validator->fails()) {
            return $this->back($validator->errors());
        }

        $user->updatePassword($request->string('password'));
        Session::flash('success', t('account.password_updated'));

        return $this->redirect('/compte/parametres');
    }

    // ------------------------------------------------------------- Adresses

    public function addresses(Request $request): Response
    {
        return $this->view('front/account/addresses', [
            'title'     => t('account.addresses'),
            'addresses' => AddressRepository::forUser($this->user()->id),
        ]);
    }

    public function storeAddress(Request $request): Response
    {
        if ($errors = $this->validateAddress($request)) {
            return $this->back($errors, $request->all());
        }
        AddressRepository::create($this->user()->id, $request->all());
        Session::flash('success', t('account.address_saved'));

        return $this->redirect('/compte/adresses');
    }

    public function updateAddress(Request $request, string $id): Response
    {
        $this->ownAddress((int) $id);
        if ($errors = $this->validateAddress($request)) {
            return $this->back($errors, $request->all());
        }
        AddressRepository::update((int) $id, $this->user()->id, $request->all());
        Session::flash('success', t('account.address_saved'));

        return $this->redirect('/compte/adresses');
    }

    public function deleteAddress(Request $request, string $id): Response
    {
        AddressRepository::delete((int) $id, $this->user()->id);
        Session::flash('success', t('account.address_deleted'));

        return $this->redirect('/compte/adresses');
    }

    public function defaultAddress(Request $request, string $id): Response
    {
        $this->ownAddress((int) $id);
        AddressRepository::setDefault((int) $id, $this->user()->id);

        return $this->redirect('/compte/adresses');
    }

    // ---------------------------------------------------------- Abonnements

    public function subscriptions(Request $request): Response
    {
        return $this->view('front/account/subscriptions', [
            'title'         => t('account.subscriptions'),
            'subscriptions' => SubscriptionRepository::forUser($this->user()->id),
        ]);
    }

    public function renewSubscription(Request $request, string $id): Response
    {
        SubscriptionRepository::renew((int) $id, $this->user()->id);
        Session::flash('success', t('account.subscription_renewed'));

        return $this->redirect('/compte/abonnements');
    }

    public function cancelSubscription(Request $request, string $id): Response
    {
        SubscriptionRepository::cancel((int) $id, $this->user()->id);
        Session::flash('success', t('account.subscription_cancelled'));

        return $this->redirect('/compte/abonnements');
    }

    public function reactivateSubscription(Request $request, string $id): Response
    {
        SubscriptionRepository::reactivate((int) $id, $this->user()->id);
        Session::flash('success', t('account.subscription_reactivated'));

        return $this->redirect('/compte/abonnements');
    }

    // ------------------------------------------------------------ Commandes

    public function orders(Request $request): Response
    {
        $user = $this->user();
        $year = $request->has('year') ? $request->int('year') : null;

        return $this->view('front/account/orders', [
            'title'  => t('account.orders'),
            'orders' => OrderRepository::forUser($user->id, $year, $request->string('q')),
            'years'  => OrderRepository::yearsForUser($user->id),
            'year'   => $year,
            'q'      => $request->string('q'),
        ]);
    }

    public function orderDetail(Request $request, string $id): Response
    {
        $order = $this->ownOrder((int) $id);

        return $this->view('front/account/order_detail', [
            'title' => t('account.order') . ' ' . $order['invoice_number'],
            'order' => $order,
            'items' => OrderRepository::items((int) $order['id']),
        ]);
    }

    public function invoice(Request $request, string $id): Response
    {
        $order = $this->ownOrder((int) $id);

        // Facture générée par Stripe : on redirige vers le PDF hébergé par Stripe.
        if (($order['stripe_invoice_url'] ?? null) !== null && $order['stripe_invoice_url'] !== '') {
            return $this->redirect((string) $order['stripe_invoice_url']);
        }

        // Sinon, facture maison générée à la volée (TVA + remise incluses).
        $pdf = InvoicePdf::generate($order, OrderRepository::items((int) $order['id']));

        return Response::download($pdf, 'facture-' . $order['invoice_number'] . '.pdf', 'application/pdf');
    }

    // --------------------------------------------------------------- Privé

    private function user(): User
    {
        return Auth::user() ?? throw new HttpException(403, 'Authentification requise.');
    }

    /** @return array<string,list<string>> Erreurs de validation (vide si OK). */
    private function validateAddress(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'first_name'  => 'required|max:80',
            'last_name'   => 'required|max:80',
            'line1'       => 'required|max:180',
            'city'        => 'required|max:120',
            'postal_code' => 'required|max:20',
            'country'     => 'required|max:80',
        ]);

        return $validator->fails() ? $validator->errors() : [];
    }

    private function ownAddress(int $id): void
    {
        if (AddressRepository::find($id, $this->user()->id) === null) {
            throw new HttpException(404, t('account.address_not_found'));
        }
    }

    /** @return array<string,mixed> */
    private function ownOrder(int $id): array
    {
        $order = OrderRepository::findForUser($id, $this->user()->id);
        if ($order === null) {
            throw new HttpException(404, t('account.order_not_found'));
        }

        return $order;
    }
}
