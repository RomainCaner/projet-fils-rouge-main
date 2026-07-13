package com.cyna.admin.security;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;

import com.warrenstrange.googleauth.GoogleAuthenticator;
import com.warrenstrange.googleauth.GoogleAuthenticatorKey;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.mindrot.jbcrypt.BCrypt;

/**
 * Tests unitaires de la logique de sécurité de l'authentification administrateur.
 *
 * Couvre les exigences de la note de cadrage (§5.3) : hachage bcrypt des mots de
 * passe et authentification à deux facteurs TOTP.
 */
class CredentialUtilsTest {

    @Test
    @DisplayName("Un mot de passe correct est reconnu contre son hachage bcrypt $2a")
    void passwordMatchesValidBcryptHash() {
        String hash = BCrypt.hashpw("Admin@1234", BCrypt.gensalt());

        assertTrue(CredentialUtils.passwordMatches("Admin@1234", hash));
    }

    @Test
    @DisplayName("Un mot de passe incorrect est rejeté")
    void passwordDoesNotMatchWrongPassword() {
        String hash = BCrypt.hashpw("Admin@1234", BCrypt.gensalt());

        assertFalse(CredentialUtils.passwordMatches("MauvaisMotDePasse", hash));
    }

    @Test
    @DisplayName("Un hachage PHP en $2y est normalisé en $2a et reste vérifiable")
    void passwordMatchesNormalizedPhpHash() {
        // bcrypt calcule le même résultat pour $2a et $2y ; on simule un hachage
        // PHP en remplaçant le préfixe de version.
        String hashA = BCrypt.hashpw("Client@1234", BCrypt.gensalt());
        String hashY = hashA.replaceFirst("^\\$2a\\$", "\\$2y\\$");

        assertTrue(CredentialUtils.passwordMatches("Client@1234", hashY));
    }

    @Test
    @DisplayName("La normalisation remplace $2y et $2b par $2a")
    void normalizeRewritesVersionPrefix() {
        assertEquals("$2a$10$abcdef", CredentialUtils.normalizeBcryptHash("$2y$10$abcdef"));
        assertEquals("$2a$10$abcdef", CredentialUtils.normalizeBcryptHash("$2b$10$abcdef"));
        assertEquals("$2a$10$abcdef", CredentialUtils.normalizeBcryptHash("$2a$10$abcdef"));
    }

    @Test
    @DisplayName("La normalisation d'un hachage null renvoie null")
    void normalizeNullReturnsNull() {
        assertNull(CredentialUtils.normalizeBcryptHash(null));
    }

    @Test
    @DisplayName("Les entrées nulles ou vides sont refusées sans exception")
    void passwordMatchesRejectsNullOrEmptyInputs() {
        assertFalse(CredentialUtils.passwordMatches(null, "$2a$10$abc"));
        assertFalse(CredentialUtils.passwordMatches("pwd", null));
        assertFalse(CredentialUtils.passwordMatches("pwd", ""));
    }

    @Test
    @DisplayName("Un hachage mal formé est rejeté proprement (pas de crash)")
    void passwordMatchesRejectsMalformedHash() {
        assertFalse(CredentialUtils.passwordMatches("pwd", "ceci-n-est-pas-un-hachage"));
    }

    @Test
    @DisplayName("Le code TOTP courant est accepté par la vérification 2FA")
    void totpMatchesCurrentCode() {
        GoogleAuthenticator gAuth = new GoogleAuthenticator();
        GoogleAuthenticatorKey key = gAuth.createCredentials();
        int currentCode = gAuth.getTotpPassword(key.getKey());

        assertTrue(CredentialUtils.totpMatches(key.getKey(), currentCode));
    }

    @Test
    @DisplayName("Un code TOTP erroné est rejeté")
    void totpRejectsWrongCode() {
        GoogleAuthenticator gAuth = new GoogleAuthenticator();
        GoogleAuthenticatorKey key = gAuth.createCredentials();
        int currentCode = gAuth.getTotpPassword(key.getKey());
        // Un code volontairement différent du code courant.
        int wrongCode = (currentCode == 0) ? 1 : currentCode - 1;

        assertFalse(CredentialUtils.totpMatches(key.getKey(), wrongCode));
    }

    @Test
    @DisplayName("Un secret 2FA absent est refusé")
    void totpRejectsMissingSecret() {
        assertFalse(CredentialUtils.totpMatches(null, 123456));
        assertFalse(CredentialUtils.totpMatches("", 123456));
    }
}
