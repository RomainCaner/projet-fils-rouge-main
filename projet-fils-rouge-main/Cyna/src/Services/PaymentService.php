<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Core\Config;

/**
 * Service de paiement s'appuyant sur Stripe (mode test).
 *
 * Le numéro de carte n'est JAMAIS transmis au serveur : il est saisi côté
 * client dans le Stripe Payment Element (iframe servie par js.stripe.com) puis
 * confirmé directement auprès de Stripe via la clé publique. Le serveur se
 * contente de :
 *   1. créer un PaymentIntent (montant + devise) et renvoyer son client_secret ;
 *   2. relire le PaymentIntent après confirmation pour vérifier qu'il est bien
 *      « succeeded » avant d'enregistrer la commande.
 *
 * Aucune donnée de carte n'est conservée côté serveur : seul l'identifiant
 * opaque du PaymentIntent ainsi que la marque et les 4 derniers chiffres
 * (retournés par Stripe) sont stockés à titre informatif sur la commande.
 *
 * À défaut de clés (ex. démonstration sans compte Stripe), le service bascule
 * en mode simulé afin que le parcours d'achat reste testable de bout en bout.
 */
final class PaymentService
{
    private const STRIPE_API = 'https://api.stripe.com/v1';

    public static function isConfigured(): bool
    {
        return (string) Config::get('stripe.secret') !== ''
            && (string) Config::get('stripe.publishable') !== '';
    }

    /**
     * Crée un PaymentIntent et renvoie son identifiant + client_secret.
     *
     * Le client_secret est transmis au navigateur pour permettre la
     * confirmation de la carte directement auprès de Stripe (Payment Element).
     *
     * @return array{id:string,client_secret:string}
     */
    public static function createIntent(int $amountCents, string $description): array
    {
        $currency = strtolower((string) Config::get('stripe.currency', 'eur'));

        if (!self::isConfigured()) {
            // Mode simulé : pas d'appel réseau, secret factice.
            $id = 'pi_simulated_' . bin2hex(random_bytes(8));

            return ['id' => $id, 'client_secret' => $id . '_secret_' . bin2hex(random_bytes(8))];
        }

        $intent = self::stripeRequest('POST', '/payment_intents', [
            'amount'                                     => $amountCents,
            'currency'                                   => $currency,
            'description'                                => $description,
            'automatic_payment_methods[enabled]'         => 'true',
            'automatic_payment_methods[allow_redirects]' => 'never',
        ]);

        return [
            'id'            => (string) ($intent['id'] ?? ''),
            'client_secret' => (string) ($intent['client_secret'] ?? ''),
        ];
    }

    /**
     * Relit un PaymentIntent auprès de Stripe pour vérifier le paiement.
     *
     * @return array{success:bool,status:string,amount:int,brand:string,last4:string,payment_intent:string,error:?string}
     */
    public static function verify(string $intentId): array
    {
        if (!self::isConfigured()) {
            // Mode simulé : tout PaymentIntent simulé est réputé réglé.
            $ok = str_starts_with($intentId, 'pi_simulated_');

            return self::result($ok, $ok ? 'succeeded' : 'failed', 0, 'card', '0000', $intentId, $ok ? null : 'Paiement invalide.');
        }

        try {
            $intent = self::stripeRequest('GET', '/payment_intents/' . rawurlencode($intentId), [
                'expand[]' => 'latest_charge',
            ]);

            $status  = (string) ($intent['status'] ?? '');
            $amount  = (int) ($intent['amount'] ?? 0);
            $card    = $intent['latest_charge']['payment_method_details']['card'] ?? [];
            $brand   = (string) ($card['brand'] ?? 'card');
            $last4   = (string) ($card['last4'] ?? '');
            $success = $status === 'succeeded';

            return self::result($success, $status, $amount, $brand, $last4, (string) ($intent['id'] ?? $intentId), $success ? null : 'Paiement non confirmé.');
        } catch (\Throwable $e) {
            return self::result(false, 'error', 0, 'card', '', $intentId, $e->getMessage());
        }
    }

    /**
     * Génère une facture Stripe pour une commande déjà réglée, et renvoie l'URL
     * du PDF hébergé par Stripe (ou null si Stripe n'est pas configuré / en cas
     * d'erreur — l'appelant retombe alors sur la facture maison).
     *
     * Le paiement ayant déjà eu lieu via le PaymentIntent, la facture est créée
     * à titre de justificatif puis marquée « payée hors Stripe » (paid_out_of_band).
     * La remise éventuelle est portée par une ligne de facture négative.
     *
     * @param list<array{description:string,amount:int}> $lines Lignes en centimes (TTC)
     */
    public static function createInvoice(string $email, array $lines, int $discountCents, ?string $discountCode): ?string
    {
        if (!self::isConfigured() || $email === '') {
            return null;
        }

        $currency = strtolower((string) Config::get('stripe.currency', 'eur'));

        try {
            $customer = self::stripeRequest('POST', '/customers', ['email' => $email]);
            $customerId = (string) ($customer['id'] ?? '');
            if ($customerId === '') {
                return null;
            }

            // Une ligne de facture par service.
            foreach ($lines as $line) {
                self::stripeRequest('POST', '/invoiceitems', [
                    'customer'    => $customerId,
                    'amount'      => (int) $line['amount'],
                    'currency'    => $currency,
                    'description' => (string) $line['description'],
                ]);
            }

            // Remise éventuelle : ligne négative.
            if ($discountCents > 0) {
                self::stripeRequest('POST', '/invoiceitems', [
                    'customer'    => $customerId,
                    'amount'      => -$discountCents,
                    'currency'    => $currency,
                    'description' => 'Remise' . ($discountCode !== null ? ' (' . $discountCode . ')' : ''),
                ]);
            }

            $invoice = self::stripeRequest('POST', '/invoices', [
                'customer'                       => $customerId,
                'auto_advance'                   => 'false',
                'pending_invoice_items_behavior' => 'include',
            ]);
            $invoiceId = (string) ($invoice['id'] ?? '');
            if ($invoiceId === '') {
                return null;
            }

            self::stripeRequest('POST', '/invoices/' . rawurlencode($invoiceId) . '/finalize', []);
            $paid = self::stripeRequest('POST', '/invoices/' . rawurlencode($invoiceId) . '/pay', [
                'paid_out_of_band' => 'true',
            ]);

            $pdf = (string) ($paid['invoice_pdf'] ?? '');

            return $pdf !== '' ? $pdf : null;
        } catch (\Throwable $e) {
            // Repli silencieux sur la facture maison.
            return null;
        }
    }

    /**
     * Appel HTTP à l'API Stripe (cURL, sans SDK).
     *
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    private static function stripeRequest(string $method, string $path, array $params): array
    {
        $url = self::STRIPE_API . $path;
        $ch = curl_init();

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . Config::get('stripe.secret')],
            CURLOPT_TIMEOUT        => 20,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_URL]        = $url;
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($params);
        } else {
            $options[CURLOPT_URL] = $url . ($params ? '?' . http_build_query($params) : '');
        }

        curl_setopt_array($ch, $options);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Connexion à Stripe impossible : ' . $error);
        }

        /** @var array<string,mixed> $decoded */
        $decoded = json_decode((string) $body, true) ?: [];
        if ($status >= 400) {
            throw new \RuntimeException((string) ($decoded['error']['message'] ?? 'Erreur Stripe.'));
        }

        return $decoded;
    }

    /**
     * @return array{success:bool,status:string,amount:int,brand:string,last4:string,payment_intent:string,error:?string}
     */
    private static function result(bool $success, string $status, int $amount, string $brand, string $last4, string $intent, ?string $error): array
    {
        return [
            'success'        => $success,
            'status'         => $status,
            'amount'         => $amount,
            'brand'          => $brand,
            'last4'          => $last4,
            'payment_intent' => $intent,
            'error'          => $error,
        ];
    }
}
