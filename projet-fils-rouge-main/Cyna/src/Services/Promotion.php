<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Core\Session;
use Cyna\Repositories\PromotionRepository;

/**
 * Codes de réduction appliqués au panier.
 *
 * Le code retenu est mémorisé en session (accessible aux visiteurs connectés
 * comme invités). Le calcul de la remise est isolé dans une méthode pure
 * (discountFor) afin d'être testable unitairement, indépendamment de la base.
 */
final class Promotion
{
    private const KEY = 'promo_code';

    /**
     * Calcule la remise (en centimes) pour un sous-total donné.
     * Ne dépasse jamais le sous-total et n'est jamais négative.
     *
     * @param array<string,mixed> $promo
     */
    public static function discountFor(array $promo, int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $value = max(0, (int) $promo['value']);

        $discount = ($promo['type'] === 'fixed')
            ? $value
            : (int) round($subtotalCents * min(100, $value) / 100);

        return max(0, min($discount, $subtotalCents));
    }

    /**
     * Vérifie qu'un code est utilisable. Retourne un message d'erreur (clé i18n)
     * ou null si le code est valide.
     *
     * @param array<string,mixed>|null $promo
     */
    public static function validate(?array $promo): ?string
    {
        if ($promo === null) {
            return 'promo.invalid';
        }
        if ((int) $promo['active'] !== 1) {
            return 'promo.inactive';
        }
        if ($promo['expires_at'] !== null && strtotime((string) $promo['expires_at']) < time()) {
            return 'promo.expired';
        }
        if ($promo['max_uses'] !== null && (int) $promo['uses'] >= (int) $promo['max_uses']) {
            return 'promo.exhausted';
        }

        return null;
    }

    /**
     * Tente d'appliquer un code au panier.
     *
     * @return array{ok:bool,error:?string}
     */
    public static function apply(string $code): array
    {
        $promo = PromotionRepository::findByCode($code);
        $error = self::validate($promo);

        if ($error !== null) {
            return ['ok' => false, 'error' => $error];
        }

        Session::set(self::KEY, strtoupper(trim($code)));

        return ['ok' => true, 'error' => null];
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    public static function appliedCode(): ?string
    {
        $code = Session::get(self::KEY);

        return is_string($code) && $code !== '' ? $code : null;
    }

    /**
     * Résout la remise courante pour un sous-total. Revalide le code (il a pu
     * être désactivé ou expirer entre-temps) et le retire s'il n'est plus valide.
     *
     * @return array{code:?string,title:?string,discount:int,total:int}
     */
    public static function resolve(int $subtotalCents): array
    {
        $code = self::appliedCode();
        if ($code === null) {
            return ['code' => null, 'title' => null, 'discount' => 0, 'total' => $subtotalCents];
        }

        $promo = PromotionRepository::findByCode($code);
        if (self::validate($promo) !== null) {
            self::clear();

            return ['code' => null, 'title' => null, 'discount' => 0, 'total' => $subtotalCents];
        }

        $discount = self::discountFor((array) $promo, $subtotalCents);

        return [
            'code'     => $code,
            'title'    => ($promo['title'] ?? null) !== null ? (string) $promo['title'] : null,
            'discount' => $discount,
            'total'    => $subtotalCents - $discount,
        ];
    }
}
