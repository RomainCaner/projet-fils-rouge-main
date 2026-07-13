<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Core\Totp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Vérifie l'implémentation TOTP (RFC 6238) utilisée pour la 2FA du back-office.
 *
 * Couvre l'exigence « Authentification à deux facteurs pour les administrateurs »
 * de la note de cadrage (§5.3) et le KPI « couverture des tests de sécurité ».
 */
#[CoversClass(Totp::class)]
final class TotpTest extends TestCase
{
    /**
     * Calcule le code attendu pour un instant donné en réutilisant l'algorithme
     * interne (méthode privée codeAt), afin de disposer d'un cas positif fiable
     * sans dépendre d'une horloge figée.
     */
    private function codeAt(string $secret, int $timeSlice): string
    {
        // Depuis PHP 8.1, les méthodes privées sont accessibles par réflexion
        // sans appel à setAccessible().
        $method = new ReflectionMethod(Totp::class, 'codeAt');

        return (string) $method->invoke(null, $secret, $timeSlice);
    }

    public function testGeneratedSecretHasRequestedLengthAndBase32Alphabet(): void
    {
        $secret = Totp::generateSecret();

        self::assertSame(32, strlen($secret));
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
    }

    public function testGeneratedSecretsAreUnique(): void
    {
        self::assertNotSame(Totp::generateSecret(), Totp::generateSecret());
    }

    public function testVerifyAcceptsTheCurrentCode(): void
    {
        $secret = Totp::generateSecret();
        $slice = (int) floor(time() / 30);

        self::assertTrue(Totp::verify($secret, $this->codeAt($secret, $slice)));
    }

    public function testVerifyToleratesOnePeriodOfClockDrift(): void
    {
        $secret = Totp::generateSecret();
        $slice = (int) floor(time() / 30);

        self::assertTrue(Totp::verify($secret, $this->codeAt($secret, $slice - 1)), 'code de la période précédente');
        self::assertTrue(Totp::verify($secret, $this->codeAt($secret, $slice + 1)), 'code de la période suivante');
    }

    public function testVerifyRejectsCodeOutsideTheToleranceWindow(): void
    {
        $secret = Totp::generateSecret();
        $slice = (int) floor(time() / 30);

        self::assertFalse(Totp::verify($secret, $this->codeAt($secret, $slice + 5)));
    }

    public function testVerifyIgnoresSurroundingWhitespace(): void
    {
        $secret = Totp::generateSecret();
        $slice = (int) floor(time() / 30);
        $code = $this->codeAt($secret, $slice);

        self::assertTrue(Totp::verify($secret, '  ' . $code . ' '));
    }

    /**
     * @return list<array{0:string}>
     */
    public static function malformedCodeProvider(): array
    {
        return [['12345'], ['1234567'], ['abcdef'], [''], ['12 34 56 78']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedCodeProvider')]
    public function testVerifyRejectsMalformedCodes(string $code): void
    {
        self::assertFalse(Totp::verify(Totp::generateSecret(), $code));
    }

    public function testProvisioningUriContainsSecretIssuerAndParameters(): void
    {
        $uri = Totp::provisioningUri('JBSWY3DPEHPK3PXP', 'admin@cyna-it.fr', 'Cyna');

        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
        self::assertStringContainsString('issuer=Cyna', $uri);
        self::assertStringContainsString('digits=6', $uri);
        self::assertStringContainsString('period=30', $uri);
    }
}
