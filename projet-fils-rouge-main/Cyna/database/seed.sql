-- ===========================================================================
-- CYNA — Jeu de données de démonstration
-- ---------------------------------------------------------------------------
-- À exécuter APRÈS schema.sql. Fournit un contenu cohérent pour tester
-- immédiatement le site (catalogue SOC/EDR/XDR, comptes de démonstration,
-- contenu de la page d'accueil, une commande et un abonnement d'exemple).
--
-- Comptes de démonstration :
--   • Admin  : admin@cyna-it.fr  / Admin@1234  (2FA à configurer à la 1re connexion)
--   • Client : client@cyna-it.fr / Client@1234
-- ===========================================================================

SET NAMES utf8mb4;

-- --- Catégories -------------------------------------------------------------
INSERT INTO categories (id, slug, nom, description, image, position) VALUES
(1, 'soc', 'SOC — Security Operations Center',
    'Supervision et détection des menaces en continu par nos analystes, 24h/24 et 7j/7.',
    'categories/soc.svg', 1),
(2, 'edr', 'EDR — Endpoint Detection & Response',
    'Protection avancée des postes de travail et serveurs avec réponse automatisée aux incidents.',
    'categories/edr.svg', 2),
(3, 'xdr', 'XDR — Extended Detection & Response',
    'Corrélation des signaux de sécurité sur l''ensemble du système d''information pour une vision unifiée.',
    'categories/xdr.svg', 3);

-- --- Services SaaS ----------------------------------------------------------
INSERT INTO produits
    (id, categorie_id, slug, nom, description_courte, description, specifications,
     prix_mensuel_centimes, prix_annuel_centimes, disponibilite, priorite,
     est_mis_en_avant, position_mise_en_avant, image) VALUES
(1, 1, 'cyna-soc-essential', 'Cyna SOC Essential',
    'Supervision de sécurité managée pour les PME.',
    'Cyna SOC Essential assure la surveillance continue de votre infrastructure par notre équipe d''analystes. Détection des menaces en temps réel, alertes qualifiées et accompagnement à la remédiation.',
    'Surveillance 24/7 ; Corrélation d''événements (SIEM) ; Jusqu''à 50 sources de logs ; SLA de réponse 1h ; Rapport mensuel.',
    49900, 499000, 'available', 100, 1, 1, 'products/soc-essential.svg'),
(2, 1, 'cyna-soc-enterprise', 'Cyna SOC Enterprise',
    'SOC managé haute capacité pour grands comptes.',
    'Offre SOC haut de gamme avec analystes dédiés, threat hunting proactif et intégration de vos outils existants. Pensée pour les organisations à fort enjeu de conformité.',
    'Surveillance 24/7 ; Analystes dédiés ; Threat hunting ; Sources illimitées ; SLA de réponse 15 min ; Intégration SOAR.',
    129900, 1299000, 'available', 90, 1, 2, 'products/soc-enterprise.svg'),
(3, 2, 'cyna-edr-protect', 'Cyna EDR Protect',
    'Protection des terminaux nouvelle génération.',
    'Cyna EDR Protect détecte et bloque les comportements malveillants sur vos postes et serveurs grâce à l''analyse comportementale et l''isolation automatique des machines compromises.',
    'Protection multi-OS (Windows, macOS, Linux) ; Analyse comportementale ; Isolation réseau automatique ; Console centralisée ; Jusqu''à 200 agents.',
    8900, 89000, 'available', 80, 1, 3, 'products/edr-protect.svg'),
(4, 2, 'cyna-edr-managed', 'Cyna EDR Managed',
    'EDR entièrement managé par nos experts.',
    'Toute la puissance de l''EDR, opérée par les équipes Cyna. Nous prenons en charge la configuration, le suivi des alertes et la réponse aux incidents à votre place.',
    'Toutes les fonctions EDR Protect ; Réponse aux incidents managée ; Investigation forensique ; Reporting hebdomadaire.',
    14900, 149000, 'available', 70, 0, 0, 'products/edr-managed.svg'),
(5, 3, 'cyna-xdr-unified', 'Cyna XDR Unified',
    'Détection étendue corrélant tous vos signaux.',
    'Cyna XDR Unified agrège et corrèle les signaux issus des endpoints, du réseau, du cloud et de la messagerie pour offrir une détection unifiée et réduire le temps de réaction.',
    'Corrélation multi-domaines ; Connecteurs Cloud (AWS, Azure, GCP) ; Analyse réseau (NDR) ; Tableau de bord unifié ; Automatisation des réponses.',
    199900, 1999000, 'available', 60, 1, 4, 'products/xdr-unified.svg'),
(6, 3, 'cyna-xdr-cloud', 'Cyna XDR Cloud',
    'XDR spécialisé pour les environnements cloud.',
    'Version de Cyna XDR optimisée pour les architectures cloud-natives et conteneurisées (Kubernetes), avec une surveillance fine des charges de travail.',
    'Sécurité des conteneurs ; Surveillance Kubernetes ; Détection des erreurs de configuration cloud ; Conformité CIS.',
    159900, 1599000, 'maintenance', 50, 0, 0, 'products/xdr-cloud.svg');

-- Illustrations complémentaires (carrousel fiche produit)
INSERT INTO images_produit (produit_id, chemin, alt, position) VALUES
(1, 'products/soc-dashboard.svg', 'Tableau de bord du SOC Cyna', 1),
(1, 'products/soc-alerts.svg', 'Vue des alertes de sécurité', 2),
(5, 'products/xdr-graph.svg', 'Graphe de corrélation XDR', 1);

-- --- Utilisateurs -----------------------------------------------------------
-- Mots de passe : Admin@1234 / Client@1234 (hachés en bcrypt, coût 10)
INSERT INTO utilisateurs (id, nom_complet, email, mot_de_passe_hache, role, email_verifie_le, totp_actif) VALUES
(1, 'Sophie Admin', 'admin@cyna-it.fr',
    '$2b$10$eDBqck5d21caoQsY5OcvKOYzdzPwc4WZ.cJD.BDxGJEHtM3OkecLS', 'admin', NOW(), 0),
(2, 'Marc Client', 'client@cyna-it.fr',
    '$2b$10$NQ5ryv/qcTy/HPp3TkVFG.XBub6g9XBBFrbMevQ.RIcxiKbh3uuBS', 'customer', NOW(), 0);

-- Adresse de facturation du client de démonstration
INSERT INTO adresses (utilisateur_id, prenom, nom, ligne1, ville, region, code_postal, pays, telephone, par_defaut) VALUES
(2, 'Marc', 'Client', '10 rue de Penthièvre', 'Paris', 'Île-de-France', '75008', 'France', '+33123456789', 1);

-- --- Commande et abonnement d'exemple --------------------------------------
INSERT INTO commandes
    (id, utilisateur_id, numero_facture, email, statut, total_centimes, devise,
     facturation_nom, facturation_ligne1, facturation_ville, facturation_region,
     facturation_code_postal, facturation_pays, paiement_marque, paiement_quatre_derniers, cree_le) VALUES
(1, 2, 'CYNA-2026-000001', 'client@cyna-it.fr', 'active', 499000, 'EUR',
    'Marc Client', '10 rue de Penthièvre', 'Paris', 'Île-de-France', '75008', 'France',
    'visa', '4242', '2026-01-15 10:30:00');

INSERT INTO lignes_commande (commande_id, produit_id, produit_nom, periodicite, quantite, prix_unitaire_centimes, total_ligne_centimes) VALUES
(1, 1, 'Cyna SOC Essential', 'annual', 1, 499000, 499000);

INSERT INTO abonnements (utilisateur_id, produit_id, commande_id, produit_nom, periodicite, quantite, statut, debute_le, renouvelle_le) VALUES
(2, 1, 1, 'Cyna SOC Essential', 'annual', 1, 'active', '2026-01-15 10:30:00', '2027-01-15 10:30:00');

-- --- Suivi d'installation lié à la commande payée --------------------------
-- Alimente l'onglet « Installations » du back-office Java.
INSERT INTO installations (commande_id, statut_installation, commentaires) VALUES
(1, 'INSTALLED', 'Déploiement réalisé et validé avec le client.');

-- --- Contenu de la page d'accueil ------------------------------------------
INSERT INTO diapositives_accueil (titre, sous_titre, image, lien_url, position, active) VALUES
('Protégez votre entreprise', 'Des solutions de cybersécurité managées, prêtes à l''emploi.', 'slides/slide-1.svg', '/categorie/soc', 1, 1),
('Détection 24/7', 'Notre SOC veille pendant que vous dormez.', 'slides/slide-2.svg', '/categorie/soc', 2, 1),
('Réponse automatisée', 'Bloquez les menaces avant qu''elles ne se propagent.', 'slides/slide-3.svg', '/categorie/edr', 3, 1);

INSERT INTO reglages (cle, valeur) VALUES
('home_intro_text', 'Cyna accompagne les entreprises dans la sécurisation de leur système d''information grâce à des services SaaS éprouvés : SOC, EDR et XDR. Simplicité de souscription, transparence des tarifs, expertise française.'),
('home_featured_title', 'Les Top Produits du moment'),
('pagination_per_page', '9'),
('dark_mode', '0');

-- Codes de réduction de démonstration
INSERT INTO codes_reduction (code, titre, type, valeur, actif, utilisations_max) VALUES
    ('BIENVENUE10', 'Offre de bienvenue',  'percent', 10, 1, NULL),
    ('CYNA25',      'Promotion Cyna -25%', 'percent', 25, 1, 100),
    ('SECURE50',    'Remise sécurité 50€', 'fixed',   5000, 1, NULL);

-- Remise à zéro des compteurs AUTO_INCREMENT après insertion explicite des id
ALTER TABLE categories   AUTO_INCREMENT = 4;
ALTER TABLE produits     AUTO_INCREMENT = 7;
ALTER TABLE utilisateurs AUTO_INCREMENT = 3;
ALTER TABLE commandes    AUTO_INCREMENT = 2;
