<?php

declare(strict_types=1);

namespace Cyna\Core;

/**
 * Authentification à deux facteurs basée sur TOTP (RFC 6238).
 *
 * Compatible avec Google Authenticator / Authy. Implémentation autonome :
 * génération du secret en Base32, calcul du code HMAC-SHA1 et vérification
 * avec une fenêtre de tolérance pour absorber le décalage d'horloge.
 */
final class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Génère un secret aléatoire encodé en Base32 (160 bits). */
    public static function generateSecret(int $length = 32): string
    {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32[ord($bytes[$i]) & 31];
        }

        return $secret;
    }

    /**
     * Vérifie un code saisi par l'utilisateur.
     * La fenêtre ±1 période tolère un léger décalage d'horloge.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / self::PERIOD);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** URI otpauth:// à encoder dans un QR code pour l'enrôlement. */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD,
        );
    }

    private static function codeAt(string $secret, int $timeSlice): string
    {
        $key = self::base32Decode($secret);
        $binary = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binary, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = substr($hash, $offset, 4);
        $value = unpack('N', $part)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $secret): string
    {
        $secret = rtrim(strtoupper($secret), '=');
        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos(self::BASE32, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr((int) bindec($byte));
            }
        }

        return $binary;
    }
}
