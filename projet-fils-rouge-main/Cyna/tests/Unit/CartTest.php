<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Services\Cart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la logique de panier stockée en session (indépendante de la base).
 *
 * Le panier étant accessible aux visiteurs connectés comme non connectés, ces
 * tests valident la mécanique de lignes (produit + périodicité), de quantités
 * et de comptage, sans dépendre de ProductRepository.
 */
#[CoversClass(Cart::class)]
final class CartTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testCartStartsEmpty(): void
    {
        self::assertTrue(Cart::isEmpty());
        self::assertSame(0, Cart::count());
    }

    public function testAddingAProductCreatesALine(): void
    {
        Cart::add(1, 'monthly');

        self::assertFalse(Cart::isEmpty());
        self::assertSame(1, Cart::count());
    }

    public function testAddingSameProductAndPeriodIncrementsQuantity(): void
    {
        Cart::add(1, 'monthly');
        Cart::add(1, 'monthly', 2);

        self::assertSame(3, Cart::count(), 'les quantités du même produit/période se cumulent');
    }

    public function testSameProductWithDifferentPeriodsAreDistinctLines(): void
    {
        Cart::add(1, 'monthly');
        Cart::add(1, 'annual');

        // Deux lignes distinctes d'une unité chacune.
        self::assertSame(2, Cart::count());
    }

    public function testInvalidPeriodFallsBackToMonthly(): void
    {
        Cart::add(1, 'weekly'); // périodicité inconnue => monthly
        Cart::add(1, 'monthly');

        // Les deux ajouts ciblent la même ligne « monthly ».
        self::assertSame(2, Cart::count());
    }

    public function testUpdateQuantityReplacesTheQuantity(): void
    {
        Cart::add(1, 'monthly', 2);
        Cart::updateQuantity(1, 'monthly', 5);

        self::assertSame(5, Cart::count());
    }

    public function testUpdateQuantityToZeroRemovesTheLine(): void
    {
        Cart::add(1, 'monthly');
        Cart::updateQuantity(1, 'monthly', 0);

        self::assertTrue(Cart::isEmpty());
    }

    public function testUpdateQuantityOnMissingLineDoesNothing(): void
    {
        Cart::updateQuantity(42, 'monthly', 3);

        self::assertTrue(Cart::isEmpty());
    }

    public function testRemoveDeletesOnlyTheTargetedLine(): void
    {
        Cart::add(1, 'monthly');
        Cart::add(2, 'annual');
        Cart::remove(1, 'monthly');

        self::assertSame(1, Cart::count());
    }

    public function testClearEmptiesTheCart(): void
    {
        Cart::add(1, 'monthly');
        Cart::add(2, 'annual');
        Cart::clear();

        self::assertTrue(Cart::isEmpty());
        self::assertSame(0, Cart::count());
    }
}
