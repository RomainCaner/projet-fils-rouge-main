# Livrables documentaires — Projet Cyna

Ce dossier regroupe les livrables documentaires du projet fil rouge, en
complément du code source et des documents propres au site web
(`../Cyna/docs/`).

## Index

| Livrable | Fichier | Exigence |
|----------|---------|----------|
| Document de Conception Technique | [DCT_Dossier_Conception_Technique.md](DCT_Dossier_Conception_Technique.md) | Note de cadrage §1.1 / CDC §XIX-5 |
| Cahier de recettes | [CAHIER_DE_RECETTES.md](CAHIER_DE_RECETTES.md) | Note de cadrage §6.3, §7.2 |
| Documentation API (OpenAPI/Swagger) | [api/openapi.yaml](api/openapi.yaml) | Note de cadrage §4.4 |
| Manuel utilisateur | [MANUEL_UTILISATEUR.md](MANUEL_UTILISATEUR.md) | Note de cadrage §1.1, §6.3 |
| Infrastructure : sauvegardes & SSL | [INFRA_SAUVEGARDE_SSL.md](INFRA_SAUVEGARDE_SSL.md) | Note de cadrage §4.4, §5.3 |
| Guide de séparation des dépôts Git | [GUIDE_SEPARATION_REPOS.md](GUIDE_SEPARATION_REPOS.md) | Note de cadrage §1.1, §4.4 |
| Nouvelles fonctionnalités (Stripe, promos, support, CSV) | [NOUVELLES_FONCTIONNALITES.md](NOUVELLES_FONCTIONNALITES.md) | Évolutions v1.1 |
| Déploiement sur VPS gratuit (Oracle Always Free) | [DEPLOIEMENT_ORACLE.md](DEPLOIEMENT_ORACLE.md) | Mise en production |

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
