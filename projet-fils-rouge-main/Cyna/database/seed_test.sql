-- ===========================================================================
-- CYNA — Jeu de données de TEST EN VOLUME (tests de grande envergure)
-- ---------------------------------------------------------------------------
-- À exécuter APRÈS schema.sql + seed.sql. Génère, via une procédure stockée,
-- un grand nombre de produits, clients, commandes, lignes, abonnements,
-- installations et messages de contact, pour éprouver le front et le back-office
-- (pagination, tri, recherche, tableaux volumineux, graphiques du dashboard).
--
-- Tous les libellés sont préfixés "Test"/"TEST" pour rester identifiables et ne
-- jamais entrer en collision avec les données de démonstration de seed.sql.
-- Les clients générés ont le mot de passe : Client@1234
--
-- Réglage du volume : voir l'appel CALL gen_test_data(...) en bas de fichier.
-- ===========================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS gen_test_data;
DELIMITER $$
CREATE PROCEDURE gen_test_data(
    IN nProducts INT,
    IN nUsers    INT,
    IN nOrders   INT,
    IN nMessages INT
)
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE j INT DEFAULT 0;
    DECLARE nLines INT;
    DECLARE vUser INT;
    DECLARE vEmail VARCHAR(190);
    DECLARE vOrder INT;
    DECLARE vStatus VARCHAR(20);
    DECLARE vTotal INT;
    DECLARE pid INT;
    DECLARE pnom VARCHAR(160);
    DECLARE pprix INT;

    -- ---------------------------------------------------------------- Produits
    SET i = 0;
    WHILE i < nProducts DO
        SET i = i + 1;
        INSERT INTO produits
            (categorie_id, slug, nom, description_courte, description, specifications,
             prix_mensuel_centimes, prix_annuel_centimes, disponibilite, priorite,
             est_mis_en_avant, position_mise_en_avant, image)
        VALUES (
            1 + FLOOR(RAND() * 3),
            CONCAT('test-prod-', LPAD(i, 4, '0')),
            CONCAT('Service Test ', LPAD(i, 4, '0')),
            'Produit de test généré pour la démonstration de volume.',
            'Description détaillée du produit de test, générée automatiquement pour les essais de charge, de pagination et d''affichage du catalogue.',
            'Surveillance ; Reporting ; Intégration ; Support.',
            FLOOR(2000 + RAND() * 200000),
            FLOOR(20000 + RAND() * 2000000),
            IF(RAND() < 0.85, 'available', 'maintenance'),
            FLOOR(RAND() * 100),
            IF(RAND() < 0.08, 1, 0),
            0,
            ELT(1 + FLOOR(RAND() * 6),
                'products/soc-essential.svg', 'products/soc-enterprise.svg', 'products/edr-protect.svg',
                'products/edr-managed.svg', 'products/xdr-unified.svg', 'products/xdr-cloud.svg')
        );
    END WHILE;

    -- ----------------------------------------------------------- Utilisateurs
    SET i = 0;
    WHILE i < nUsers DO
        SET i = i + 1;
        INSERT INTO utilisateurs
            (nom_complet, email, mot_de_passe_hache, role, email_verifie_le, totp_actif)
        VALUES (
            CONCAT('Client Test ', LPAD(i, 4, '0')),
            CONCAT('testuser', LPAD(i, 4, '0'), '@cyna-test.fr'),
            '$2b$10$NQ5ryv/qcTy/HPp3TkVFG.XBub6g9XBBFrbMevQ.RIcxiKbh3uuBS', -- Client@1234
            'customer', NOW(), 0
        );
        INSERT INTO adresses
            (utilisateur_id, prenom, nom, ligne1, ville, region, code_postal, pays, telephone, par_defaut)
        VALUES (
            LAST_INSERT_ID(), 'Client', CONCAT('Test', LPAD(i, 4, '0')),
            CONCAT(FLOOR(1 + RAND() * 200), ' rue de la Démonstration'),
            ELT(1 + FLOOR(RAND() * 5), 'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nantes'),
            'France', LPAD(FLOOR(RAND() * 95000), 5, '0'), 'France', '+33100000000', 1
        );
    END WHILE;

    -- --------------------------------------- Commandes + lignes + abonnements
    SET i = 0;
    WHILE i < nOrders DO
        SET i = i + 1;
        SELECT id, email INTO vUser, vEmail
            FROM utilisateurs WHERE role = 'customer' ORDER BY RAND() LIMIT 1;
        SET vStatus = ELT(1 + FLOOR(RAND() * 6),
            'pending', 'paid', 'active', 'renewed', 'cancelled', 'failed');
        SET vTotal = FLOOR(5000 + RAND() * 500000);

        INSERT INTO commandes
            (utilisateur_id, numero_facture, email, statut, total_centimes, devise,
             facturation_nom, facturation_ligne1, facturation_ville, facturation_region,
             facturation_code_postal, facturation_pays, paiement_marque, paiement_quatre_derniers, cree_le)
        VALUES (
            vUser, CONCAT('CYNA-TEST-', LPAD(i, 6, '0')), vEmail, vStatus, vTotal, 'EUR',
            CONCAT('Client Test ', LPAD(i, 4, '0')), '1 rue de la Démonstration',
            'Paris', 'Île-de-France', '75000', 'France',
            ELT(1 + FLOOR(RAND() * 3), 'visa', 'mastercard', 'amex'),
            LPAD(FLOOR(RAND() * 10000), 4, '0'),
            DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY)
        );
        SET vOrder = LAST_INSERT_ID();

        SET nLines = 1 + FLOOR(RAND() * 3);
        SET j = 0;
        WHILE j < nLines DO
            SET j = j + 1;
            SELECT id, nom, prix_mensuel_centimes INTO pid, pnom, pprix
                FROM produits ORDER BY RAND() LIMIT 1;
            INSERT INTO lignes_commande
                (commande_id, produit_id, produit_nom, periodicite, quantite,
                 prix_unitaire_centimes, total_ligne_centimes)
            VALUES (
                vOrder, pid, pnom, IF(RAND() < 0.5, 'monthly', 'annual'),
                1, pprix, pprix
            );
        END WHILE;

        -- Une installation par commande honorée (alimente l'onglet back-office)
        IF vStatus IN ('paid', 'active', 'renewed') THEN
            INSERT IGNORE INTO installations (commande_id, statut_installation, commentaires)
            VALUES (
                vOrder,
                ELT(1 + FLOOR(RAND() * 4), 'PENDING', 'IN PROGRESS', 'INSTALLED', 'ON HOLD'),
                'Installation générée automatiquement pour les tests de volume.'
            );
        END IF;

        -- Abonnement actif pour les commandes en cours
        IF vStatus IN ('active', 'renewed') THEN
            INSERT INTO abonnements
                (utilisateur_id, produit_id, commande_id, produit_nom, periodicite, quantite,
                 statut, debute_le, renouvelle_le)
            VALUES (
                vUser, pid, vOrder, pnom, 'annual', 1, 'active',
                DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 300) DAY),
                DATE_ADD(NOW(), INTERVAL 365 DAY)
            );
        END IF;
    END WHILE;

    -- ------------------------------------------------- Messages de contact
    SET i = 0;
    WHILE i < nMessages DO
        SET i = i + 1;
        INSERT INTO messages_contact (email, sujet, corps, statut, cree_le)
        VALUES (
            CONCAT('prospect', LPAD(i, 4, '0'), '@exemple.fr'),
            ELT(1 + FLOOR(RAND() * 4),
                'Demande de devis', 'Question technique', 'Demande de support', 'Proposition de partenariat'),
            'Message de test généré automatiquement pour valider la volumétrie et l''affichage du back-office.',
            ELT(1 + FLOOR(RAND() * 3), 'new', 'read', 'archived'),
            DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 200) DAY)
        );
    END WHILE;
END$$
DELIMITER ;

-- Volume généré : (produits, clients, commandes, messages)
SET autocommit = 0;
CALL gen_test_data(80, 300, 1000, 250);
COMMIT;
SET autocommit = 1;

DROP PROCEDURE gen_test_data;
