# Livrables documentaires — Projet Cyna

Ce dossier regroupe les livrables documentaires du projet fil rouge, en
complément du code source et des documents propres au site web
(`../Cyna/docs/`).

## Index

### Documents Bloc 3 (livraison, mise en production & exploitation)

| Livrable | Fichier | Exigence |
|----------|---------|----------|
| **DAT — Dossier d'Architecture Technique** (document chapeau) | [DAT_Dossier_Architecture_Technique.md](DAT_Dossier_Architecture_Technique.md) | Bloc 3.2 |
| Pipeline CI/CD (automatisation) | [PIPELINE_CICD.md](PIPELINE_CICD.md) | Bloc 3.1 — Automatisation |
| Supervision & logs applicatifs | [SUPERVISION_LOGS.md](SUPERVISION_LOGS.md) | Bloc 3.1 — Supervision |
| Plan de tests de performance / charge | [PLAN_TESTS_PERFORMANCE.md](PLAN_TESTS_PERFORMANCE.md) | Bloc 3.1 — Tests & performances |
| Gestion des incidents & bugs | [GESTION_INCIDENTS_BUGS.md](GESTION_INCIDENTS_BUGS.md) | Bloc 3.1 — Incidents & bugs |
| Bilan du projet (prévisionnel vs réalisé) | [BILAN_PROJET.md](BILAN_PROJET.md) | Bloc 3.1 — Bilan · CDC §XIX-6 |

### Documents Bloc 2 (conception) et transverses

| Livrable | Fichier | Exigence |
|----------|---------|----------|
| Document de Conception Technique | [DCT_Dossier_Conception_Technique.md](DCT_Dossier_Conception_Technique.md) | Note de cadrage §1.1 / CDC §XIX-5 |
| Cahier de recettes | [CAHIER_DE_RECETTES.md](CAHIER_DE_RECETTES.md) | Note de cadrage §6.3, §7.2 |
| Documentation API (OpenAPI/Swagger) | [api/openapi.yaml](api/openapi.yaml) | Note de cadrage §4.4 / CDC §XIX-5 |
| Manuel utilisateur | [MANUEL_UTILISATEUR.md](MANUEL_UTILISATEUR.md) | Note de cadrage §1.1, §6.3 |
| Infrastructure : sauvegardes & SSL | [INFRA_SAUVEGARDE_SSL.md](INFRA_SAUVEGARDE_SSL.md) | Note de cadrage §4.4, §5.3 |
| Guide de séparation des dépôts Git | [GUIDE_SEPARATION_REPOS.md](GUIDE_SEPARATION_REPOS.md) | Note de cadrage §1.1, §4.4 |
| Nouvelles fonctionnalités (Stripe, promos, support, CSV) | [NOUVELLES_FONCTIONNALITES.md](NOUVELLES_FONCTIONNALITES.md) | Évolutions v1.1 |
| Déploiement sur VPS gratuit (Oracle Always Free) | [DEPLOIEMENT_ORACLE.md](DEPLOIEMENT_ORACLE.md) | Mise en production |

### Traçabilité du cahier des charges (CDC §XIX-5 — Documentation technique)

| Exigence CDC | Document couvrant l'exigence |
|--------------|------------------------------|
| 1. Guide d'installation (dépendances, env. dev/prod, déploiement) | [`../Cyna/docs/INSTALLATION.md`](../Cyna/docs/INSTALLATION.md) + [DEPLOIEMENT_ORACLE.md](DEPLOIEMENT_ORACLE.md) |
| 2. Documentation des API (endpoints, méthodes, paramètres, réponses) | [api/openapi.yaml](api/openapi.yaml) |
| 3. Structure du code (architecture, composants, choix technos) | [`../Cyna/docs/ARCHITECTURE.md`](../Cyna/docs/ARCHITECTURE.md) + DCT §5 |
| 4. Tests (unitaires, intégration, fonctionnels) | [CAHIER_DE_RECETTES.md](CAHIER_DE_RECETTES.md) + [PLAN_TESTS_PERFORMANCE.md](PLAN_TESTS_PERFORMANCE.md) |
| 5. DCT (architecture, diagrammes, sécurité, maintenance) | [DCT_Dossier_Conception_Technique.md](DCT_Dossier_Conception_Technique.md) |
| 6. Suivi des livrables (sprints, progression) | [BILAN_PROJET.md](BILAN_PROJET.md) §4 + [MODIFS_REUNION_TUTEUR.md](MODIFS_REUNION_TUTEUR.md) |

> **Périmètre.** Conformément à la décision projet, le **chatbot** (CDC §XV) est
> **hors périmètre** ; le formulaire de contact classique est livré. L'application
> mobile native est remplacée par un **site responsive mobile-first** (arbitrage
> MoSCoW) — voir [BILAN_PROJET.md](BILAN_PROJET.md) §2.

## Versions PDF (dépôt formel)

Les documents sont également disponibles au format **PDF** (schémas rendus en
images) dans le dossier [`pdf/`](pdf/), prêts à être déposés :

- `pdf/DCT_Dossier_Conception_Technique.pdf`
- `pdf/Cahier_de_recettes.pdf`
- `pdf/Manuel_utilisateur.pdf`
- `pdf/Guide_separation_depots_Git.pdf`
- `pdf/Infrastructure_sauvegardes_SSL.pdf`
- `pdf/Deploiement_Oracle.pdf`

## Voir la documentation API dans Swagger UI

Le fichier `api/openapi.yaml` est un contrat OpenAPI 3.0 standard. Pour le
visualiser :

- coller le contenu dans [editor.swagger.io](https://editor.swagger.io/) ; ou
- l'importer dans **Postman** (Import → OpenAPI) pour générer une collection de
  requêtes.

## Tests automatisés

| Suite | Commande | Emplacement |
|-------|----------|-------------|
| PHPUnit (site web) | `cd ../Cyna && composer install && composer test` | `../Cyna/tests/Unit` |
| JUnit (application Java) | `mvn test` (à la racine) | `../src/test/java` |

Les deux suites sont exécutées automatiquement par l'intégration continue
(`../.github/workflows/ci.yml`).
