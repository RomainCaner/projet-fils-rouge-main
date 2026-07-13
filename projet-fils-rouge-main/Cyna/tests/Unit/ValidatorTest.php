<?php

declare(strict_types=1);

namespace Cyna\Tests\Unit;

use Cyna\Core\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie la validation serveur des formulaires (rempart anti-données malveillantes).
 *
 * Couvre la règle de robustesse du mot de passe imposée par la note de cadrage
 * (§5.3 : « minimum 8 caractères, majuscule, minuscule, chiffre, caractère spécial »).
 */
#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    public function testValidDataPasses(): void
    {
        $validator = Validator::make(
            ['email' => 'marc@pme.fr', 'name' => 'Marc Durand'],
            ['email' => 'required|email', 'name' => 'required|min:3|max:50'],
        );

        self::assertFalse($validator->fails());
        self::assertSame([], $validator->errors());
    }

    public function testRequiredFieldMissingFails(): void
    {
        $validator = Validator::make([], ['email' => 'required|email']);

        self::assertTrue($validator->fails());
        self::assertArrayHasKey('email', $validator->errors());
    }

    public function testOptionalEmptyFieldIsNotValidatedFurther(): void
    {
        // 'address2' est optionnel : vide, il ne doit déclencher aucune règle min.
        $validator = Validator::make(['address2' => ''], ['address2' => 'min:5']);

        self::assertFalse($validator->fails());
    }

    /**
     * @return list<array{0:string,1:bool}>
     */
    public static function emailProvider(): array
    {
        return [
            ['marc@pme.fr', true],
            ['user@sub.domain.co', true],
            ['not-an-email', false],
            ['missing@tld', false],
            ['@nodomain.fr', false],
        ];
    }

    #[DataProvider('emailProvider')]
    public function testEmailRule(string $email, bool $expectedValid): void
    {
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);

        self::assertSame($expectedValid, !$validator->fails());
    }

    /**
     * @return list<array{0:string,1:bool}>
     */
    public static function passwordProvider(): array
    {
        return [
            ['Client@1234', true],   // conforme
            ['Str0ng!Pass', true],   // conforme
            ['short1!A', true],      // 8 caractères pile, tous les types
            ['alllowercase1!', false], // pas de majuscule
            ['ALLUPPERCASE1!', false], // pas de minuscule
            ['NoDigits!!', false],     // pas de chiffre
            ['NoSpecial123', false],   // pas de caractère spécial
            ['Aa1!', false],           // trop court
        ];
    }

    #[DataProvider('passwordProvider')]
    public function testPasswordStrengthRule(string $password, bool $expectedValid): void
    {
        $validator = Validator::make(['password' => $password], ['password' => 'required|password']);

        self::assertSame($expectedValid, !$validator->fails());
    }

    public function testConfirmedRule(): void
    {
        $ok = Validator::make(
            ['password' => 'Secret@123', 'password_confirmation' => 'Secret@123'],
            ['password' => 'confirmed'],
        );
        $ko = Validator::make(
            ['password' => 'Secret@123', 'password_confirmation' => 'Different@1'],
            ['password' => 'confirmed'],
        );

        self::assertFalse($ok->fails());
        self::assertTrue($ko->fails());
    }

    public function testInRule(): void
    {
        $ok = Validator::make(['period' => 'annual'], ['period' => 'in:monthly,annual']);
        $ko = Validator::make(['period' => 'weekly'], ['period' => 'in:monthly,annual']);

        self::assertFalse($ok->fails());
        self::assertTrue($ko->fails());
    }

    public function testNumericAndMaxRules(): void
    {
        $ok = Validator::make(['qty' => '5'], ['qty' => 'numeric|max:2']);
        $ko = Validator::make(['qty' => 'abc'], ['qty' => 'numeric']);

        self::assertFalse($ok->fails(), 'un nombre à un chiffre respecte numeric|max:2');
        self::assertTrue($ko->fails());
    }

    public function testCustomLabelAppearsInMessage(): void
    {
        $validator = Validator::make(
            [],
            ['email' => 'required'],
            ['email' => 'Adresse e-mail'],
        );

        self::assertStringContainsString('Adresse e-mail', $validator->allMessages()[0]);
    }
}
