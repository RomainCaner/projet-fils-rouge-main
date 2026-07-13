<?php

declare(strict_types=1);

namespace Cyna\Controllers\Front;

use Cyna\Core\Controller;
use Cyna\Core\Exceptions\HttpException;
use Cyna\Core\Mailer;
use Cyna\Core\Request;
use Cyna\Core\Response;
use Cyna\Core\Session;
use Cyna\Core\Validator;
use Cyna\Core\View;
use Cyna\Repositories\AddressRepository;
use Cyna\Repositories\OrderRepository;
use Cyna\Repositories\PromotionRepository;
use Cyna\Repositories\SubscriptionRepository;
use Cyna\Services\Auth;
use Cyna\Services\Cart;
use Cyna\Services\PaymentService;
use Cyna\Services\Promotion;

/**
 * Tunnel de commande : connexion/invité → adresse de facturation → paiement
 * sécurisé (Stripe Elements) → confirmation.
 *
 * La carte est saisie et confirmée côté client dans le Payment Element : aucune
 * donnée bancaire ne transite par le serveur. Un éventuel code de réduction
 * s'applique sur le montant débité.
 */
final class CheckoutController extends Controller
{
    public function show(Request $request): Response
    {
        if (Cart::isEmpty()) {
            Session::flash('error', t('cart.empty'));

            return $this->redirect('/panier');
        }

        $user = Auth::user();
        $promo = Promotion::resolve(Cart::total());

        // Crée le PaymentIntent pour le montant remisé et conserve son
        // identifiant en session : la carte sera confirmée côté client.
        $intent = PaymentService::createIntent($promo['total'], 'Commande Cyna');
        Session::set('checkout_intent', $intent['id']);

        return $this->view('front/checkout', [
            'title'            => t('checkout.title'),
            'lines'            => Cart::lines(),
            'subtotal'         => Cart::total(),
            'discount'         => $promo['discount'],
            'promoCode'        => $promo['code'],
            'total'            => $promo['total'],
            'hasUnavailable'   => Cart::hasUnavailable(),
            'user'             => $user,
            'addresses'        => $user ? AddressRepository::forUser($user->id) : [],
            'stripeKey'        => (string) \Cyna\Core\Config::get('stripe.publishable'),
            'clientSecret'     => $intent['client_secret'],
            'paymentIntentId'  => $intent['id'],
            'stripeConfigured' => PaymentService::isConfigured(),
        ]);
    }

    public function process(Request $request): Response
    {
        if (Cart::isEmpty() || Cart::hasUnavailable()) {
            Session::flash('error', t('checkout.cart_problem'));

            return $this->redirect('/panier');
        }

        $user = Auth::user();

        $rules = [
            'first_name'  => 'required|max:80',
            'last_name'   => 'required|max:80',
            'line1'       => 'required|max:180',
            'city'        => 'required|max:120',
            'postal_code' => 'required|max:20',
            'country'     => 'required|max:80',
        ];
        if ($user === null) {
            $rules['email'] = 'required|email';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return $this->back($validator->errors(), $request->all());
        }

        // Montant réellement dû après application éventuelle d'un code de réduction.
        $promo = Promotion::resolve(Cart::total());

        // La carte a été confirmée côté client par Stripe Elements. On vérifie
        // ici que le PaymentIntent reçu correspond à celui créé en session et
        // qu'il est réellement réglé — aucune donnée de carte ne transite.
        $intentId = $request->string('payment_intent_id');
        if ($intentId === '' || $intentId !== (string) Session::get('checkout_intent')) {
            Session::flash('error', t('checkout.payment_failed'));

            return $this->back([], $request->all());
        }

        $payment = PaymentService::verify($intentId);

        // Sécurité : le paiement doit avoir réussi et porter sur le montant remisé.
        $amountOk = $payment['amount'] === 0 || $payment['amount'] === $promo['total'];
        if (!$payment['success'] || !$amountOk) {
            Session::flash('error', t('checkout.payment_failed') . ' ' . ($payment['error'] ?? ''));

            return $this->back([], $request->all());
        }

        Session::forget('checkout_intent');

        $order = $this->createOrder($request, $user, $payment, $promo);

        // Comptabilise l'utilisation du code de réduction et le retire de la session.
        if ($promo['code'] !== null) {
            PromotionRepository::incrementUsage($promo['code']);
            Promotion::clear();
        }

        // Création des abonnements (clients connectés uniquement).
        if ($user !== null) {
            foreach (Cart::lines() as $line) {
                SubscriptionRepository::create(
                    $user->id,
                    (int) $line['product']['id'],
                    $order['id'],
                    (string) $line['product']['name'],
                    $line['period'],
                    $line['quantity'],
                );
            }
            $this->persistAccountData($request, $user->id);
        }

        // Facture générée par Stripe (si clés configurées) ; sinon la facture
        // maison (InvoicePdf) sera produite à la demande.
        $invoiceLines = array_map(static fn (array $l): array => [
            'description' => (string) $l['product']['name'] . ' x' . (int) $l['quantity']
                . ' (' . ($l['period'] === 'annual' ? 'Annuel' : 'Mensuel') . ')',
            'amount'      => (int) $l['line_total'],
        ], Cart::lines());

        $invoiceUrl = PaymentService::createInvoice(
            $user?->email ?? $request->string('email'),
            $invoiceLines,
            (int) $promo['discount'],
            $promo['code'],
        );
        if ($invoiceUrl !== null) {
            OrderRepository::setInvoiceUrl($order['id'], $invoiceUrl);
        }

        // E-mail de confirmation de commande, avec la facture PDF en pièce jointe.
        $this->sendOrderConfirmation($order['id']);

        Cart::clear();
        Session::set('last_invoice', $order['invoice_number']);

        return $this->redirect('/checkout/confirmation/' . $order['invoice_number']);
    }

    public function confirmation(Request $request, string $invoice): Response
    {
        // Accessible seulement au propriétaire (ou juste après l'achat en invité).
        $order = OrderRepository::findByInvoice($invoice);
        $user = Auth::user();
        $isOwner = $order !== null && (
            Session::get('last_invoice') === $invoice
            || ($user !== null && (int) $order['user_id'] === $user->id)
        );

        if (!$isOwner) {
            throw new HttpException(403, t('checkout.confirmation_denied'));
        }

        return $this->view('front/confirmation', [
            'title' => t('checkout.confirmation_title'),
            'order' => $order,
            'items' => OrderRepository::items((int) $order['id']),
        ]);
    }

    /**
     * Envoie l'e-mail de confirmation de commande au client, avec un lien de
     * téléchargement de la facture (Stripe si disponible, sinon facture maison
     * via l'espace compte).
     *
     * Volontairement SANS pièce jointe : identique à l'e-mail d'inscription qui
     * fonctionne de façon fiable. Les messages avec pièce jointe issus d'un
     * domaine non authentifié (SPF/DKIM) sont massivement filtrés par les
     * messageries — un lien garantit la délivrabilité. Toute erreur d'envoi est
     * journalisée sans interrompre le parcours (la commande est déjà réglée).
     */
    private function sendOrderConfirmation(int $orderId): void
    {
        try {
            $order = OrderRepository::find($orderId);
            if ($order === null || (string) $order['email'] === '') {
                return;
            }

            $items = OrderRepository::items($orderId);

            // Lien vers la facture : PDF hébergé par Stripe si présent, sinon la
            // facture maison téléchargeable depuis l'espace compte.
            $invoiceUrl = ((string) ($order['stripe_invoice_url'] ?? '')) !== ''
                ? (string) $order['stripe_invoice_url']
                : url('/compte/commandes/' . $order['id'] . '/facture');

            $html = (new View())->render('emails/order_confirmation', [
                'subject'    => t('email.order_subject'),
                'order'      => $order,
                'items'      => $items,
                'invoiceUrl' => $invoiceUrl,
            ]);

            Mailer::send((string) $order['email'], t('email.order_subject'), $html);
        } catch (\Throwable $e) {
            error_log('E-mail de confirmation de commande échoué : ' . $e->getMessage());
        }
    }

    /**
     * @param array{success:bool,brand:string,last4:string,payment_intent:string} $payment
     * @param array{code:?string,discount:int,total:int}                          $promo
     * @return array{id:int,invoice_number:string}
     */
    private function createOrder(Request $request, ?\Cyna\Models\User $user, array $payment, array $promo): array
    {
        $items = array_map(static fn (array $line): array => [
            'product_id'       => (int) $line['product']['id'],
            'product_name'     => (string) $line['product']['name'],
            'billing_period'   => $line['period'],
            'quantity'         => $line['quantity'],
            'unit_price_cents' => $line['unit_price'],
        ], Cart::lines());

        return OrderRepository::createWithItems([
            'user_id'               => $user?->id,
            'email'                 => $user?->email ?? $request->string('email'),
            'status'                => 'active',
            'total_cents'           => $promo['total'],
            'discount_code'         => $promo['code'],
            'discount_cents'        => $promo['discount'],
            'currency'              => 'EUR',
            'billing_name'          => $request->string('first_name') . ' ' . $request->string('last_name'),
            'billing_line1'         => $request->string('line1'),
            'billing_line2'         => $request->string('line2') ?: null,
            'billing_city'          => $request->string('city'),
            'billing_region'        => $request->string('region') ?: null,
            'billing_postal_code'   => $request->string('postal_code'),
            'billing_country'       => $request->string('country'),
            'payment_brand'         => $payment['brand'],
            'payment_last4'         => $payment['last4'],
            'stripe_payment_intent' => $payment['payment_intent'],
        ], $items);
    }

    /**
     * Enregistre uniquement l'adresse de facturation si l'utilisateur le
     * demande. Aucune carte n'est jamais conservée (paiement Stripe sans
     * enregistrement du moyen de paiement).
     */
    private function persistAccountData(Request $request, int $userId): void
    {
        if ($request->boolean('save_address')) {
            AddressRepository::create($userId, [
                'first_name'  => $request->string('first_name'),
                'last_name'   => $request->string('last_name'),
                'line1'       => $request->string('line1'),
                'line2'       => $request->string('line2'),
                'city'        => $request->string('city'),
                'region'      => $request->string('region'),
                'postal_code' => $request->string('postal_code'),
                'country'     => $request->string('country'),
                'phone'       => $request->string('phone'),
            ]);
        }
    }
}
