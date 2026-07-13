<?php

declare(strict_types=1);

namespace Cyna\Models;

use Cyna\Core\Database;

/**
 * Entité Utilisateur (clients et administrateurs).
 *
 * Porte les données de compte et les comportements liés à l'authentification :
 * vérification d'e-mail, jetons de confirmation/réinitialisation, 2FA.
 *
 * La base est en français ; les colonnes sont aliasées vers les clés du domaine
 * applicatif (full_name, password_hash, ...) au sein de cette couche d'accès.
 */
final class User
{
    /** Liste des colonnes (français) aliasées vers les clés applicatives. */
    private const SELECT = 'id, nom_complet AS full_name, email, mot_de_passe_hache AS password_hash,
        role, email_verifie_le AS email_verified_at, secret_totp AS totp_secret, totp_actif AS totp_enabled';

    public function __construct(
        public int $id,
        public string $fullName,
        public string $email,
        public string $passwordHash,
        public string $role,
        public ?string $emailVerifiedAt,
        public ?string $totpSecret,
        public bool $totpEnabled,
    ) {
    }

    /** @param array<string,mixed> $row */
    private static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['full_name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['role'],
            $row['email_verified_at'] !== null ? (string) $row['email_verified_at'] : null,
            $row['totp_secret'] !== null ? (string) $row['totp_secret'] : null,
            (bool) $row['totp_enabled'],
        );
    }

    public static function find(int $id): ?self
    {
        $row = Database::fetchOne('SELECT ' . self::SELECT . ' FROM utilisateurs WHERE id = ?', [$id]);

        return $row ? self::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $row = Database::fetchOne('SELECT ' . self::SELECT . ' FROM utilisateurs WHERE email = ?', [$email]);

        return $row ? self::fromRow($row) : null;
    }

    public static function findByConfirmationToken(string $token): ?self
    {
        $row = Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM utilisateurs WHERE jeton_confirmation = ? AND confirmation_expire_le > NOW()',
            [$token],
        );

        return $row ? self::fromRow($row) : null;
    }

    public static function findByResetToken(string $token): ?self
    {
        $row = Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM utilisateurs WHERE jeton_reinitialisation = ? AND reinitialisation_expire_le > NOW()',
            [$token],
        );

        return $row ? self::fromRow($row) : null;
    }

    public static function findByRememberToken(string $token): ?self
    {
        $row = Database::fetchOne(
            'SELECT ' . self::SELECT . ' FROM utilisateurs WHERE jeton_memorisation = ?',
            [$token],
        );

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Crée un compte client avec un jeton de confirmation d'e-mail.
     *
     * @return array{0:int,1:string} identifiant et jeton de confirmation
     */
    public static function createCustomer(string $fullName, string $email, string $plainPassword): array
    {
        $token = bin2hex(random_bytes(32));
        Database::run(
            'INSERT INTO utilisateurs (nom_complet, email, mot_de_passe_hache, role, jeton_confirmation, confirmation_expire_le)
             VALUES (?, ?, ?, "customer", ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))',
            [$fullName, $email, password_hash($plainPassword, PASSWORD_BCRYPT), $token],
        );

        return [Database::lastInsertId(), $token];
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    /** Marque l'e-mail comme vérifié et consomme le jeton de confirmation. */
    public function markEmailVerified(): void
    {
        Database::run(
            'UPDATE utilisateurs SET email_verifie_le = NOW(), jeton_confirmation = NULL, confirmation_expire_le = NULL WHERE id = ?',
            [$this->id],
        );
    }

    public static function setResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        Database::run(
            'UPDATE utilisateurs SET jeton_reinitialisation = ?, reinitialisation_expire_le = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?',
            [$token, $userId],
        );

        return $token;
    }

    public function resetPassword(string $plainPassword): void
    {
        Database::run(
            'UPDATE utilisateurs SET mot_de_passe_hache = ?, jeton_reinitialisation = NULL, reinitialisation_expire_le = NULL WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_BCRYPT), $this->id],
        );
    }

    public function updateProfile(string $fullName): void
    {
        Database::run('UPDATE utilisateurs SET nom_complet = ? WHERE id = ?', [$fullName, $this->id]);
    }

    public function updateEmail(string $email): void
    {
        Database::run('UPDATE utilisateurs SET email = ? WHERE id = ?', [$email, $this->id]);
    }

    public function updatePassword(string $plainPassword): void
    {
        Database::run(
            'UPDATE utilisateurs SET mot_de_passe_hache = ? WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_BCRYPT), $this->id],
        );
    }

    public function setRememberToken(?string $token): void
    {
        Database::run('UPDATE utilisateurs SET jeton_memorisation = ? WHERE id = ?', [$token, $this->id]);
    }

    /** Enregistre le secret 2FA et active la double authentification. */
    public function enableTotp(string $secret): void
    {
        Database::run('UPDATE utilisateurs SET secret_totp = ?, totp_actif = 1 WHERE id = ?', [$secret, $this->id]);
    }
}
