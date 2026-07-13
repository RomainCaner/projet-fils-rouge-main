# Dossier d'Architecture Technique (DAT)

**Projet fil rouge — Plateforme e-commerce & gestion SaaS « Cyna »**
Équipe : Adrien CACHOUX · Romain CANER · Ethan MIRBEAU
Version 1.0 — **Bloc 3 (livraison & mise en production)**

---

> **Positionnement du document.** Le DAT est le document **chapeau** du Bloc 3.
> Là où le [DCT](DCT_Dossier_Conception_Technique.md) décrit la *conception*
> (choix d'ingénierie, modèle de données, sécurité *by design*), le DAT décrit la
> **solution telle que livrée et exploitée** : architecture déployée,
> automatisation, supervision, exploitation, tests de charge, gestion des
> incidents. Il agrège et renvoie vers les livrables détaillés du dossier
> `docs/`.

## Table des matières

1. [Objet et périmètre](#1-objet-et-périmètre)
2. [Répartition rédactionnelle par candidat](#2-répartition-rédactionnelle-par-candidat)
3. [Architecture technique déployée](#3-architecture-technique-déployée)
4. [Environnements](#4-environnements)
5. [Chaîne de livraison (CI/CD)](#5-chaîne-de-livraison-cicd)
6. [Exploitation : supervision, logs, sauvegardes](#6-exploitation--supervision-logs-sauvegardes)
7. [Sécurité en production](#7-sécurité-en-production)
8. [Stratégie de tests et de performance](#8-stratégie-de-tests-et-de-performance)
9. [Gestion des incidents et des bugs](#9-gestion-des-incidents-et-des-bugs)
10. [Matrice de traçabilité des livrables Bloc 3](#10-matrice-de-traçabilité-des-livrables-bloc-3)
11. [Annexes et références](#11-annexes-et-références)

---

## 1. Objet et périmètre

Ce document prouve que la solution conçue aux blocs 1 et 2 a été **développée,
mise en production, sécurisée, supervisée, automatisée et testée**. Il couvre les
deux composants livrés :

- le **site web e-commerce + back-office** (PHP 8.2 natif, MySQL 8, conteneurisé) ;
- l'**application d'administration Java 17 Swing** (client lourd de bureau).

Rappel de périmètre (matrice MoSCoW, note de cadrage §4.3) : l'application mobile
native est remplacée par un site **responsive mobile-first** ; le chatbot est
hors périmètre.

## 2. Répartition rédactionnelle par candidat

Le DAT est un livrable **collectif unique**, chaque candidat étant responsable
d'une partie (exigence Bloc 3). Répartition proposée :

| Candidat | Partie du DAT | Chapitres |
|----------|---------------|-----------|
| Adrien CACHOUX | Déploiement, infrastructure, exploitation | §3, §4, §6 |
| Romain CANER | Chaîne de livraison (CI/CD), tests & performance | §5, §8 |
| Ethan MIRBEAU | Sécurité en production, gestion des incidents & bugs | §7, §9 |

> À adapter selon la répartition réelle des tâches de développement de l'équipe.

## 3. Architecture technique déployée

### 3.1 Vue d'ensemble

```
                          Internet (HTTPS)
                               │
                      ┌────────▼─────────┐
                      │  Caddy (reverse   │  TLS Let's Encrypt automatique
                      │  proxy + HTTPS)   │  duckdns / domaine
                      └────────┬─────────┘
                               │ HTTP interne
                      ┌────────▼─────────┐
                      │  Conteneur web    │  PHP 8.2 + Apache
                      │  (site + API      │  front controller public/index.php
                      │   back-office)    │
                      └────────┬─────────┘
                               │ PDO (requêtes préparées)
                      ┌────────▼─────────┐
                      │  Conteneur MySQL  │  MySQL 8 (InnoDB)
                      │  volume persistant│  schéma partagé
                      └────────┬─────────┘
                               │ TCP 3306 (accès restreint, ponctuel)
                      ┌────────▼─────────┐
                      │  App Java Swing   │  poste administrateur (.jar/.exe)
                      │  (client lourd)   │  hors serveur
                      └──────────────────┘
```

Les services de développement additionnels (Mailpit pour la capture des e-mails,
phpMyAdmin) ne sont **pas** exposés en production.

### 3.2 Composants et rôles

| Composant | Technologie | Rôle | Hébergement |
|-----------|-------------|------|-------------|
| Reverse proxy | Caddy | Terminaison TLS, redirection HTTP→HTTPS, en-têtes | VM Oracle |
| Application web | PHP 8.2 / Apache | Site e-commerce + back-office + API interne | Conteneur Docker |
| Base de données | MySQL 8 (InnoDB) | Persistance, partagée web ↔ Java | Conteneur Docker + volume |
| Client administrateur | Java 17 Swing | Gestion back-office hors navigateur | Poste local (distribué) |
| Paiement | Stripe (API REST) | Tokenisation, encaissement | SaaS externe |

Le détail des couches applicatives (Kernel, middlewares, services, repositories)
figure dans [`../Cyna/docs/ARCHITECTURE.md`](../Cyna/docs/ARCHITECTURE.md).

## 4. Environnements

| Environnement | Cible | Configuration | Données |
|---------------|-------|---------------|---------|
| **Développement** | Poste développeur (Docker Compose) | `.env.docker`, `APP_ENV=local`, Mailpit, phpMyAdmin | Jeu de démo (`seed.sql`) |
| **Intégration continue** | GitHub Actions (runner éphémère) | PHP 8.2 + JDK 17, base non requise pour les tests unitaires | — |
| **Production** | VM Oracle Cloud Always Free | `APP_ENV=production`, HTTPS Caddy, secrets injectés | Données réelles + sauvegardes |

Procédure de mise en place détaillée : [`DEPLOIEMENT_ORACLE.md`](DEPLOIEMENT_ORACLE.md).

## 5. Chaîne de livraison (CI/CD)

Résumé : chaque *push* / *pull request* déclenche le pipeline GitHub Actions
(`.github/workflows/ci.yml`) qui **lint** le code PHP, exécute **PHPUnit** (web)
et **JUnit** (Java). Le déploiement suit une procédure de **CD semi-automatisée**
documentée séparément.

➡️ Voir le livrable dédié : [`PIPELINE_CICD.md`](PIPELINE_CICD.md).

## 6. Exploitation : supervision, logs, sauvegardes

- **Supervision & logs applicatifs** : [`SUPERVISION_LOGS.md`](SUPERVISION_LOGS.md).
- **Sauvegardes & certificats SSL** : [`INFRA_SAUVEGARDE_SSL.md`](INFRA_SAUVEGARDE_SSL.md).

## 7. Sécurité en production

Les mesures de sécurité *by design* (injection SQL, XSS, CSRF, sessions, 2FA
TOTP, hachage bcrypt, RGPD) sont décrites dans le [DCT §6](DCT_Dossier_Conception_Technique.md#6-plan-de-sécurité)
et vérifiées par la matrice de tests du [Cahier de recettes](CAHIER_DE_RECETTES.md).
Les aspects propres à l'exploitation (HTTPS obligatoire, en-têtes de sécurité,
restriction de l'accès MySQL au poste Java, secrets hors dépôt) sont couverts par
[`INFRA_SAUVEGARDE_SSL.md`](INFRA_SAUVEGARDE_SSL.md) et [`DEPLOIEMENT_ORACLE.md`](DEPLOIEMENT_ORACLE.md).

## 8. Stratégie de tests et de performance

- Tests unitaires, d'intégration et de sécurité : [Cahier de recettes](CAHIER_DE_RECETTES.md).
- Tests de **performance / charge** (scénarios, outillage, résultats) :
  [`PLAN_TESTS_PERFORMANCE.md`](PLAN_TESTS_PERFORMANCE.md).

## 9. Gestion des incidents et des bugs

Processus de suivi des bugs, classification par sévérité, traitement des erreurs
récurrentes et registre d'incidents : [`GESTION_INCIDENTS_BUGS.md`](GESTION_INCIDENTS_BUGS.md).

## 10. Matrice de traçabilité des livrables Bloc 3

| Exigence Bloc 3.1 / 3.2 | Livrable | État |
|--------------------------|----------|------|
| Déploiement & mise en production | `DEPLOIEMENT_ORACLE.md`, `INSTALLATION.md` | ✅ |
| Supervision & logs applicatifs | `SUPERVISION_LOGS.md` | ✅ |
| Automatisation (CI/CD) | `PIPELINE_CICD.md`, `.github/workflows/ci.yml` | ✅ |
| Sécurité applicative | DCT §6, `CAHIER_DE_RECETTES.md` | ✅ |
| Tests unitaires / intégration / fonctionnels | `CAHIER_DE_RECETTES.md` | ✅ |
| Tests de performance / charge | `PLAN_TESTS_PERFORMANCE.md` | ✅ |
| Gestion des incidents & bugs | `GESTION_INCIDENTS_BUGS.md` | ✅ |
| Bilan du projet (prévisionnel vs réalisé) | `BILAN_PROJET.md` | ✅ |
| Documentation API | `api/openapi.yaml` | ✅ |
| Manuel utilisateur | `MANUEL_UTILISATEUR.md` | ✅ |

## 11. Annexes et références

- [DCT — Dossier de Conception Technique](DCT_Dossier_Conception_Technique.md)
- [Cahier de recettes](CAHIER_DE_RECETTES.md)
- [Pipeline CI/CD](PIPELINE_CICD.md)
- [Supervision & logs](SUPERVISION_LOGS.md)
- [Plan de tests de performance](PLAN_TESTS_PERFORMANCE.md)
- [Gestion des incidents & bugs](GESTION_INCIDENTS_BUGS.md)
- [Bilan du projet](BILAN_PROJET.md)
- [Infrastructure — sauvegardes & SSL](INFRA_SAUVEGARDE_SSL.md)
- [Déploiement Oracle Cloud](DEPLOIEMENT_ORACLE.md)
- [Architecture applicative détaillée](../Cyna/docs/ARCHITECTURE.md)
- [Documentation API (OpenAPI)](api/openapi.yaml)
