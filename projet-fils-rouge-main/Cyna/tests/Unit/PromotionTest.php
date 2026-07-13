<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Services\Promotion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie le calcul de remise des codes promotionnels (fonctionnalité optionnelle
 * « promotions et codes de réduction » — MoSCoW, Could Have).
 */
#[CoversClass(Promotion::class)]
final class PromotionTest extends TestCase
{
    /**
     * @return list<array{0:array<string,mixed>,1:int,2:int}>
     */
    public static function discountProvider(): array
    {
        return [
            // [promo, sous-total, remise attendue]
            'pourcentage 10%'                => [['type' => 'percent', 'value' => 10], 10000, 1000],
            'pourcentage 25% (arrondi)'      => [['type' => 'percent', 'value' => 25], 4990, 1248],
            'montant fixe 50 €'              => [['type' => 'fixed', 'value' => 5000], 10000, 5000],
            'montant fixe plafonné au total' => [['type' => 'fixed', 'value' => 5000], 3000, 3000],
            'pourcentage > 100 plafonné'     => [['type' => 'percent', 'value' => 200], 1000, 1000],
            'sous-total nul'                 => [['type' => 'percent', 'value' => 10], 0, 0],
            'valeur négative ignorée'        => [['type' => 'fixed', 'value' => -500], 2000, 0],
        ];
    }

    /**
     * @param array<string,mixed> $promo
     */
    #[DataProvider('discountProvider')]
    public function testDiscountIsComputedAndCapped(array $promo, int $subtotal, int $expected): void
    {
        self::assertSame($expected, Promotion::discountFor($promo, $subtotal));
    }

    public function testDiscountNeverExceedsSubtotal(): void
    {
        $discount = Promotion::discountFor(['type' => 'fixed', 'value' => 999999], 4990);

        self::assertSame(4990, $discount);
    }

    public function testValidateAcceptsAValidCode(): void
    {
        $promo = ['active' => 1, 'expires_at' => null, 'max_uses' => null, 'uses' => 0];

        self::assertNull(Promotion::validate($promo));
    }

    public function testValidateRejectsUnknownCode(): void
    {
        self::assertSame('promo.invalid', Promotion::validate(null));
    }

    public function testValidateRejectsInactiveCode(): void
    {
        $promo = ['active' => 0, 'expires_at' => null, 'max_uses' => null, 'uses' => 0];

        self::assertSame('promo.inactive', Promotion::validate($promo));
    }

    public function testValidateRejectsExpiredCode(): void
    {
        $promo = ['active' => 1, 'expires_at' => '2000-01-01 00:00:00', 'max_uses' => null, 'uses' => 0];

        self::assertSame('promo.expired', Promotion::validate($promo));
    }

    public function testValidateRejectsExhaustedCode(): void
    {
        $promo = ['active' => 1, 'expires_at' => null, 'max_uses' => 100, 'uses' => 100];

        self::assertSame('promo.exhausted', Promotion::validate($promo));
    }
}
