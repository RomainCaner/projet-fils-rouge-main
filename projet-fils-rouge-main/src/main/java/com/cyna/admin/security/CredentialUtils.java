package com.cyna.admin.security;

import com.warrenstrange.googleauth.GoogleAuthenticator;
import org.mindrot.jbcrypt.BCrypt;

/**
 * Utilitaires de sécurité de l'authentification administrateur.
 *
 * <p>Cette classe isole la logique pure (sans interface graphique ni base de
 * données) afin de la rendre testable unitairement :</p>
 * <ul>
 *   <li>normalisation des hachages bcrypt provenant d'autres écosystèmes (PHP) ;</li>
 *   <li>vérification du mot de passe contre un hachage bcrypt ;</li>
 *   <li>vérification d'un code TOTP à deux facteurs (RFC 6238).</li>
 * </ul>
 *
 * <p>Elle est utilisée par {@code LoginFrame} pour l'écran de connexion du
 * back-office Java Swing.</p>
 */
public final class CredentialUtils {

    private CredentialUtils() {
        // Classe utilitaire : pas d'instanciation.
    }

    /**
     * Normalise le préfixe de version d'un hachage bcrypt.
     *
     * <p>jBCrypt ne lit que les variantes {@code $2a}, {@code $2x} et {@code $2y}.
     * Les hachages générés côté PHP utilisent souvent {@code $2y} ou {@code $2b} ;
     * seule la lettre de version diffère, le calcul bcrypt est identique. On les
     * ramène donc à {@code $2a} pour garantir la compatibilité.</p>
     *
     * @param hash hachage bcrypt d'origine (peut être {@code null})
     * @return le hachage compatible jBCrypt, ou {@code null} si l'entrée est {@code null}
     */
    public static String normalizeBcryptHash(String hash) {
        if (hash == null) {
            return null;
        }
        return hash.replaceFirst("^\\$2[by]\\$", "\\$2a\\$");
    }

    /**
     * Vérifie qu'un mot de passe en clair correspond à un hachage bcrypt.
     *
     * @param plainPassword mot de passe saisi
     * @param storedHash    hachage stocké en base (formats $2a/$2b/$2y acceptés)
     * @return {@code true} si le mot de passe correspond, {@code false} sinon
     *         (y compris si l'un des paramètres est {@code null} ou vide)
     */
    public static boolean passwordMatches(String plainPassword, String storedHash) {
        if (plainPassword == null || storedHash == null || storedHash.isEmpty()) {
            return false;
        }
        try {
            return BCrypt.checkpw(plainPassword, normalizeBcryptHash(storedHash));
        } catch (IllegalArgumentException ex) {
            // Hachage mal formé : on refuse l'accès plutôt que de propager l'erreur.
            return false;
        }
    }

    /**
     * Vérifie un code TOTP à six chiffres contre le secret d'un compte.
     *
     * @param secret secret TOTP encodé en Base32 (peut être {@code null})
     * @param code   code numérique à six chiffres saisi par l'administrateur
     * @return {@code true} si le code est valide dans la fenêtre de tolérance
     */
    public static boolean totpMatches(String secret, int code) {
        if (secret == null || secret.isEmpty()) {
            return false;
        }
        return new GoogleAuthenticator().authorize(secret, code);
    }
}
