<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Core\Csrf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la protection CSRF (jeton unique par session).
 *
 * Couvre l'exigence « token anti-CSRF unique par session sur tous les
 * formulaires » de la note de cadrage (§5.3) et le scénario de recette associé.
 */
#[CoversClass(Csrf::class)]
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsA64CharacterHexString(): void
    {
        $token = Csrf::token();

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testTokenIsStableWithinTheSameSession(): void
    {
        self::assertSame(Csrf::token(), Csrf::token());
    }

    public function testValidTokenIsAccepted(): void
    {
        $token = Csrf::token();

        self::assertTrue(Csrf::isValid($token));
    }

    public function testWrongTokenIsRejected(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::isValid('deadbeef'));
    }

    public function testNullTokenIsRejected(): void
    {
        Csrf::token();

        self::assertFalse(Csrf::isValid(null));
    }

    public function testTokenIsRejectedWhenSessionHasNone(): void
    {
        // Aucun jeton généré : toute vérification doit échouer.
        self::assertFalse(Csrf::isValid('anything'));
    }
}
