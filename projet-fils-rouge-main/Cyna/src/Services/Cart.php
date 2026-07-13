<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Core\Session;
use Cyna\Repositories\ProductRepository;

/**
 * Panier d'achat stocké en session.
 *
 * Accessible aux visiteurs connectés comme non connectés. Chaque ligne est
 * identifiée par la combinaison produit + périodicité d'abonnement, ce qui
 * permet d'ajouter un même service en mensuel ET en annuel.
 */
final class Cart
{
    private const KEY = 'cart';
    private const PERIODS = ['monthly', 'annual'];

    /** Ajoute (ou incrémente) une ligne au panier. */
    public static function add(int $productId, string $period, int $quantity = 1): void
    {
        if (!in_array($period, self::PERIODS, true)) {
            $period = 'monthly';
        }

        $items = self::raw();
        $key = self::lineKey($productId, $period);
        $items[$key] = [
            'product_id' => $productId,
            'period'     => $period,
            'quantity'   => max(1, ($items[$key]['quantity'] ?? 0) + $quantity),
        ];

        Session::set(self::KEY, $items);
    }

    public static function updateQuantity(int $productId, string $period, int $quantity): void
    {
        $items = self::raw();
        $key = self::lineKey($productId, $period);

        if (!isset($items[$key])) {
            return;
        }
        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['quantity'] = $quantity;
        }

        Session::set(self::KEY, $items);
    }

    public static function remove(int $productId, string $period): void
    {
        $items = self::raw();
        unset($items[self::lineKey($productId, $period)]);
        Session::set(self::KEY, $items);
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    /**
     * Lignes du panier enrichies des données produit et des prix calculés.
     *
     * @return list<array<string,mixed>>
     */
    public static function lines(): array
    {
        $lines = [];

        foreach (self::raw() as $item) {
            $product = ProductRepository::find((int) $item['product_id']);
            if ($product === null) {
                continue; // Produit supprimé entre-temps : on l'ignore.
            }

            $unitPrice = $item['period'] === 'annual'
                ? (int) $product['price_annual_cents']
                : (int) $product['price_monthly_cents'];

            $lines[] = [
                'product'    => $product,
                'period'     => $item['period'],
                'quantity'   => (int) $item['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * (int) $item['quantity'],
                'available'  => $product['availability'] === 'available',
            ];
        }

        return $lines;
    }

    public static function total(): int
    {
        return array_sum(array_map(static fn (array $l): int => $l['line_total'], self::lines()));
    }

    public static function count(): int
    {
        return array_sum(array_map(static fn (array $i): int => (int) $i['quantity'], self::raw()));
    }

    public static function isEmpty(): bool
    {
        return self::raw() === [];
    }

    /** Vrai si au moins un service du panier est indisponible (bloque le checkout). */
    public static function hasUnavailable(): bool
    {
        foreach (self::lines() as $line) {
            if (!$line['available']) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,array{product_id:int,period:string,quantity:int}> */
    private static function raw(): array
    {
        return (array) Session::get(self::KEY, []);
    }

    private static function lineKey(int $productId, string $period): string
    {
        return $productId . ':' . $period;
    }
}
