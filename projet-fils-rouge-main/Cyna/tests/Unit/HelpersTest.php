<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie les fonctions utilitaires globales : échappement HTML (anti-XSS),
 * mise en forme monétaire et génération de slugs d'URL.
 */
final class HelpersTest extends TestCase
{
    /**
     * @return list<array{0:int,1:string}>
     */
    public static function moneyProvider(): array
    {
        return [
            [4990, '49,90 €'],
            [0, '0,00 €'],
            [150000, '1 500,00 €'],
            [99, '0,99 €'],
        ];
    }

    #[DataProvider('moneyProvider')]
    public function testMoneyFormatsCentsToEuros(int $cents, string $expected): void
    {
        self::assertSame($expected, \money($cents));
    }

    /**
     * @return list<array{0:string,1:string}>
     */
    public static function slugProvider(): array
    {
        return [
            ['Cyna EDR Pro', 'cyna-edr-pro'],
            ['SOC & EDR', 'soc-edr'],
            ['  Multiple   Spaces  ', 'multiple-spaces'],
            ['XDR-2024', 'xdr-2024'],
        ];
    }

    #[DataProvider('slugProvider')]
    public function testSlugifyProducesUrlFriendlyIdentifiers(string $input, string $expected): void
    {
        self::assertSame($expected, \slugify($input));
    }

    public function testEscapeNeutralisesHtmlToPreventXss(): void
    {
        $escaped = \e("<script>alert('xss')</script>");

        self::assertStringNotContainsString('<script>', $escaped);
        self::assertStringContainsString('&lt;script&gt;', $escaped);
        self::assertStringContainsString('&#039;', $escaped, "les apostrophes sont échappées (ENT_QUOTES)");
    }

    public function testEscapeHandlesNullGracefully(): void
    {
        self::assertSame('', \e(null));
    }
}
