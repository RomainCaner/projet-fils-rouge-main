-- ===========================================================================
-- CYNA — Schéma de la base de données (MySQL 8 / InnoDB / utf8mb4)
-- ---------------------------------------------------------------------------
-- Nomenclature en français (tables et colonnes). Base relationnelle
-- centralisant produits, utilisateurs, commandes, abonnements, adresses et
-- paiements. Partagée entre le site web (PHP) et l'application Java Swing.
--
-- Conventions :
--   • montants stockés en CENTIMES (entiers) pour éviter toute imprécision ;
--   • les valeurs d'énumération (statuts, périodicités) restent en anglais car
--     elles servent d'identifiants techniques côté applicatif et i18n.
-- ===========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --- Catégories de services -------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(120) NOT NULL,
    nom         VARCHAR(120) NOT NULL,
    description TEXT NULL,
    image       VARCHAR(255) NULL,
    position    INT NOT NULL DEFAULT 0,          -- ordre d'affichage (back-office)
    cree_le     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Services SaaS (produits) ----------------------------------------------
DROP TABLE IF EXISTS produits;
CREATE TABLE produits (
    id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categorie_id           INT UNSIGNED NOT NULL,
    slug                   VARCHAR(160) NOT NULL,
    nom                    VARCHAR(160) NOT NULL,
    description_courte     VARCHAR(255) NOT NULL,
    description            TEXT NOT NULL,
    specifications         TEXT NULL,                       -- caractéristiques techniques
    prix_mensuel_centimes  INT UNSIGNED NOT NULL DEFAULT 0,
    prix_annuel_centimes   INT UNSIGNED NOT NULL DEFAULT 0,
    disponibilite          ENUM('available','maintenance') NOT NULL DEFAULT 'available',
    priorite               INT NOT NULL DEFAULT 0,          -- tri catalogue (plus haut = en premier)
    est_mis_en_avant       TINYINT(1) NOT NULL DEFAULT 0,   -- « Top produit du moment »
    position_mise_en_avant INT NOT NULL DEFAULT 0,
    image                  VARCHAR(255) NULL,               -- visuel principal
    cree_le                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produits_slug (slug),
    KEY idx_produits_categorie (categorie_id),
    KEY idx_produits_tri (disponibilite, priorite),
    CONSTRAINT fk_produits_categorie FOREIGN KEY (categorie_id)
        REFERENCES categories (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Illustrations du carrousel produit ------------------------------------
DROP TABLE IF EXISTS images_produit;
CREATE TABLE images_produit (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produit_id INT UNSIGNED NOT NULL,
    chemin     VARCHAR(255) NOT NULL,
    alt        VARCHAR(160) NULL,
    position   INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_images_produit (produit_id),
    CONSTRAINT fk_images_produit FOREIGN KEY (produit_id)
        REFERENCES produits (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Utilisateurs (clients et administrateurs) -----------------------------
DROP TABLE IF EXISTS utilisateurs;
CREATE TABLE utilisateurs (
    id                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_complet              VARCHAR(160) NOT NULL,
    email                    VARCHAR(190) NOT NULL,
    mot_de_passe_hache       VARCHAR(255) NOT NULL,
    role                     ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    email_verifie_le         DATETIME NULL,
    jeton_confirmation       CHAR(64) NULL,
    confirmation_expire_le   DATETIME NULL,
    jeton_reinitialisation   CHAR(64) NULL,
    reinitialisation_expire_le DATETIME NULL,
    jeton_memorisation       CHAR(64) NULL,
    secret_totp              VARCHAR(64) NULL,               -- secret 2FA (admins)
    totp_actif               TINYINT(1) NOT NULL DEFAULT 0,
    cree_le                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_utilisateurs_email (email),
    KEY idx_utilisateurs_confirmation (jeton_confirmation),
    KEY idx_utilisateurs_reinitialisation (jeton_reinitialisation)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Carnet d'adresses ------------------------------------------------------
DROP TABLE IF EXISTS adresses;
CREATE TABLE adresses (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id INT UNSIGNED NOT NULL,
    prenom         VARCHAR(80) NOT NULL,
    nom            VARCHAR(80) NOT NULL,
    ligne1         VARCHAR(180) NOT NULL,
    ligne2         VARCHAR(180) NULL,
    ville          VARCHAR(120) NOT NULL,
    region         VARCHAR(120) NULL,
    code_postal    VARCHAR(20) NOT NULL,
    pays           VARCHAR(80) NOT NULL DEFAULT 'France',
    telephone      VARCHAR(30) NULL,
    par_defaut     TINYINT(1) NOT NULL DEFAULT 0,
    cree_le        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_adresses_utilisateur (utilisateur_id),
    CONSTRAINT fk_adresses_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Moyens de paiement (jamais de numéro en clair : token Stripe) ---------
DROP TABLE IF EXISTS moyens_paiement;
CREATE TABLE moyens_paiement (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id  INT UNSIGNED NOT NULL,
    marque          VARCHAR(40) NOT NULL,                   -- visa, mastercard...
    quatre_derniers CHAR(4) NOT NULL,
    mois_expiration TINYINT UNSIGNED NOT NULL,
    annee_expiration SMALLINT UNSIGNED NOT NULL,
    stripe_pm_id    VARCHAR(120) NULL,                      -- identifiant opaque Stripe
    par_defaut      TINYINT(1) NOT NULL DEFAULT 0,
    cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_moyens_paiement_utilisateur (utilisateur_id),
    CONSTRAINT fk_moyens_paiement_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Commandes --------------------------------------------------------------
DROP TABLE IF EXISTS commandes;
CREATE TABLE commandes (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id          INT UNSIGNED NULL,              -- NULL = commande invité
    numero_facture          VARCHAR(30) NOT NULL,
    email                   VARCHAR(190) NOT NULL,
    statut                  ENUM('pending','paid','active','renewed','cancelled','failed') NOT NULL DEFAULT 'pending',
    total_centimes          INT UNSIGNED NOT NULL DEFAULT 0,
    code_reduction          VARCHAR(40) NULL,               -- code promo appliqué (le cas échéant)
    remise_centimes         INT UNSIGNED NOT NULL DEFAULT 0, -- montant de la remise
    devise                  CHAR(3) NOT NULL DEFAULT 'EUR',
    -- Instantané de l'adresse de facturation au moment de la commande
    facturation_nom         VARCHAR(160) NOT NULL,
    facturation_ligne1      VARCHAR(180) NOT NULL,
    facturation_ligne2      VARCHAR(180) NULL,
    facturation_ville       VARCHAR(120) NOT NULL,
    facturation_region      VARCHAR(120) NULL,
    facturation_code_postal VARCHAR(20) NOT NULL,
    facturation_pays        VARCHAR(80) NOT NULL,
    paiement_marque         VARCHAR(40) NULL,
    paiement_quatre_derniers CHAR(4) NULL,
    stripe_payment_intent   VARCHAR(120) NULL,
    stripe_invoice_url      VARCHAR(255) NULL,             -- facture PDF hébergée par Stripe (si clés configurées)
    cree_le                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_commandes_facture (numero_facture),
    KEY idx_commandes_utilisateur (utilisateur_id),
    KEY idx_commandes_date (cree_le),
    CONSTRAINT fk_commandes_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Lignes de commande -----------------------------------------------------
DROP TABLE IF EXISTS lignes_commande;
CREATE TABLE lignes_commande (
    id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    commande_id           INT UNSIGNED NOT NULL,
    produit_id            INT UNSIGNED NULL,                -- NULL si produit supprimé
    produit_nom           VARCHAR(160) NOT NULL,            -- instantané du nom
    periodicite           ENUM('monthly','annual') NOT NULL DEFAULT 'monthly',
    quantite              INT UNSIGNED NOT NULL DEFAULT 1,
    prix_unitaire_centimes INT UNSIGNED NOT NULL DEFAULT 0,
    total_ligne_centimes  INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_lignes_commande_commande (commande_id),
    CONSTRAINT fk_lignes_commande_commande FOREIGN KEY (commande_id)
        REFERENCES commandes (id) ON DELETE CASCADE,
    CONSTRAINT fk_lignes_commande_produit FOREIGN KEY (produit_id)
        REFERENCES produits (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Abonnements ------------------------------------------------------------
DROP TABLE IF EXISTS abonnements;
CREATE TABLE abonnements (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id INT UNSIGNED NOT NULL,
    produit_id     INT UNSIGNED NULL,
    commande_id    INT UNSIGNED NULL,
    produit_nom    VARCHAR(160) NOT NULL,
    periodicite    ENUM('monthly','annual') NOT NULL DEFAULT 'monthly',
    quantite       INT UNSIGNED NOT NULL DEFAULT 1,
    statut         ENUM('active','cancelled','expired') NOT NULL DEFAULT 'active',
    renouvellement_auto TINYINT(1) NOT NULL DEFAULT 1,      -- 1 = se renouvelle seul ; 0 = résilié (actif jusqu'à l'échéance)
    debute_le      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    renouvelle_le  DATETIME NULL,                            -- fin de la période courante (renouvellement ou expiration)
    resilie_le     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_abonnements_utilisateur (utilisateur_id),
    CONSTRAINT fk_abonnements_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs (id) ON DELETE CASCADE,
    CONSTRAINT fk_abonnements_produit FOREIGN KEY (produit_id)
        REFERENCES produits (id) ON DELETE SET NULL,
    CONSTRAINT fk_abonnements_commande FOREIGN KEY (commande_id)
        REFERENCES commandes (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Carrousel de la page d'accueil (éditable au back-office) ---------------
DROP TABLE IF EXISTS diapositives_accueil;
CREATE TABLE diapositives_accueil (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre       VARCHAR(160) NOT NULL,
    sous_titre  VARCHAR(255) NULL,
    image       VARCHAR(255) NULL,
    lien_url    VARCHAR(255) NULL,
    position    INT NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Réglages clé/valeur (texte fixe de l'accueil, etc.) -------------------
DROP TABLE IF EXISTS reglages;
CREATE TABLE reglages (
    cle    VARCHAR(80) NOT NULL,
    valeur TEXT NULL,
    PRIMARY KEY (cle)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Codes de réduction (promotions) ---------------------------------------
DROP TABLE IF EXISTS codes_reduction;
CREATE TABLE codes_reduction (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code             VARCHAR(40) NOT NULL,
    titre            VARCHAR(120) NULL,              -- libellé lisible (ex : « Offre de bienvenue »)
    type             ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    valeur           INT UNSIGNED NOT NULL,          -- pourcentage (1-100) ou montant en centimes
    actif            TINYINT(1) NOT NULL DEFAULT 1,
    expire_le        DATETIME NULL,                  -- NULL = pas d'expiration
    utilisations_max INT UNSIGNED NULL,              -- NULL = illimité
    utilisations     INT UNSIGNED NOT NULL DEFAULT 0,
    cree_le          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_codes_reduction_code (code)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Messages du formulaire de contact -------------------------------------
DROP TABLE IF EXISTS messages_contact;
CREATE TABLE messages_contact (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email   VARCHAR(190) NOT NULL,
    sujet   VARCHAR(160) NOT NULL,
    corps   TEXT NOT NULL,
    statut  ENUM('new','read','archived') NOT NULL DEFAULT 'new',
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_contact_statut (statut)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --- Suivi des installations (back-office Java) -----------------------------
-- Une ligne par commande payée. Le back-office crée la ligne quand la commande
-- passe à « payé », puis suit l'avancement du déploiement chez le client.
DROP TABLE IF EXISTS installations;
CREATE TABLE installations (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    commande_id         INT UNSIGNED NOT NULL,
    statut_installation ENUM('PENDING','IN PROGRESS','INSTALLED','ON HOLD') NOT NULL DEFAULT 'PENDING',
    commentaires        TEXT NULL,
    cree_le             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_le          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    -- Une seule installation par commande (requis par les INSERT IGNORE du back-office).
    UNIQUE KEY uq_installations_commande (commande_id),
    CONSTRAINT fk_installations_commande FOREIGN KEY (commande_id)
        REFERENCES commandes (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
