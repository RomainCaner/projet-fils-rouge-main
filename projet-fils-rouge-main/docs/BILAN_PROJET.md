# Bilan du projet

**Projet fil rouge — Plateforme Cyna**
Équipe : Adrien CACHOUX · Romain CANER · Ethan MIRBEAU
Version 1.0 — Bloc 3

Ce document dresse le **bilan du projet** : écart entre le prévisionnel (blocs 1
& 2) et le réalisé (bloc 3), valeur ajoutée et perspectives. Il répond à l'axe
« Bilan du projet » du Bloc 3 et complète le suivi des livrables exigé au
CDC §XIX-6.

---

## 1. Rappel des objectifs (prévisionnel blocs 1 & 2)

La note de cadrage et le cahier des charges fixaient la réalisation d'une
plateforme e-commerce SaaS pour Cyna comprenant :

- un **site web e-commerce mobile-first** (catalogue, recherche, panier,
  checkout, compte, historique de commandes) ;
- un **back-office** de gestion (produits, commandes, utilisateurs, tableaux de
  bord) ;
- une **application mobile** (initialement prévue au CDC) ;
- un **paiement en ligne sécurisé**, une **internationalisation**, une
  **accessibilité** WCAG 2.1 et une **sécurité** renforcée.

## 2. Écart prévisionnel / réalisé

| Élément prévu (CDC / blocs 1-2) | Réalisé (bloc 3) | Écart & justification |
|---------------------------------|------------------|-----------------------|
| Site web e-commerce mobile-first | ✅ Livré (PHP 8.2 natif, responsive) | Conforme |
| Back-office (produits, commandes, tableaux de bord, 2FA) | ✅ Livré | Conforme |
| Paiement sécurisé | ✅ Stripe (tokenisation, mode test, repli simulé) | Conforme, PCI-DSS respecté |
| Internationalisation (i18n) FR/EN + socle RTL | ✅ Livré | Conforme (RTL via `dir` dynamique) |
| Accessibilité WCAG 2.1 AA | ✅ Prise en compte (contrastes, clavier, sémantique) | Conforme |
| Sécurité (SQLi, XSS, CSRF, SSL, sessions) | ✅ Livré + testé | Conforme (cf. Cahier de recettes) |
| **Application mobile native** | 🔄 Remplacée par un **site responsive mobile-first** | **Écart assumé** — arbitrage MoSCoW (note de cadrage §4.3) : ressources concentrées sur un socle web unique et maintenable |
| **Application d'administration** | ➕ **Application Java 17 Swing** (client lourd) | **Valeur ajoutée** non initialement fléchée, pour l'exploitation admin hors navigateur |
| **Chatbot** (CDC §XV) | ❌ **Hors périmètre** | **Exclusion explicite** — décision projet ; le formulaire de contact classique est bien livré |
| SPA (Single Page Application) | 🔄 Site multi-pages responsive avec chargement optimisé | **Écart assumé** — architecture serveur maîtrisée privilégiée (Green IT, empreinte réduite, SEO) |

Légende : ✅ conforme · ➕ valeur ajoutée · 🔄 réalisé différemment · ❌ non réalisé (assumé)

## 3. Bilan par axe Bloc 3

| Axe | Résultat |
|-----|----------|
| Déploiement & mise en production | Conteneurisé (Docker), déployable sur VPS gratuit (Oracle Always Free) avec HTTPS automatique |
| Supervision & logs | Logs centralisés Docker, KPI de performance, sonde de disponibilité |
| CI/CD | Pipeline GitHub Actions (lint + PHPUnit + JUnit) bloquant à la fusion |
| Sécurité | Sécurité *by design* multi-couches, 100 % des failles cibles testées |
| Tests | Suites unitaires automatisées + cahier de recettes + plan de charge |
| Incidents & bugs | Suivi GitHub Issues, sévérité, tests de non-régression |

## 4. Suivi des livrables et méthodologie (CDC §XIX-6)

- **Gestion de version** : deux dépôts Git (web / application), Git Flow
  simplifié, une branche par fonctionnalité, revue de code obligatoire, commits
  descriptifs.
- **Sprints** : développement itératif, points d'avancement et réunions tuteur
  documentés (cf. [`MODIFS_REUNION_TUTEUR.md`](MODIFS_REUNION_TUTEUR.md)).
- **Traçabilité** : chaque évolution passe par une *pull request* liée à un
  ticket et validée par la CI.

> **Point d'amélioration :** formaliser davantage les comptes rendus de sprint
> (un fichier par sprint dans `docs/reunions/`) pour renforcer la preuve du suivi
> exigée au CDC §XIX-6.

## 5. Valeur ajoutée

- **Empreinte minimale (Green IT)** : runtime web sans dépendance externe, images
  WebP, pagination — coût d'hébergement nul (VPS gratuit).
- **Sécurité éprouvée** : 2FA TOTP, tokenisation Stripe, matrice de tests de
  sécurité documentée.
- **Reproductibilité** : environnement Docker identique du poste développeur à la
  production.
- **Double interface d'administration** : back-office web + client lourd Java.

## 6. Difficultés rencontrées et solutions

| Difficulté | Solution apportée |
|------------|-------------------|
| Contrainte « aucune dépendance runtime » | Implémentation maison du noyau (routeur, TOTP, client SMTP, validateur) |
| Partage de données web ↔ Java | Base MySQL commune, schéma unique, accès restreint |
| Hébergement à coût nul | Oracle Cloud Always Free + Caddy (HTTPS Let's Encrypt) + swap sur VM AMD |
| Périmètre ambitieux (mobile + chatbot) | Arbitrage MoSCoW : recentrage sur un socle web responsive robuste |

## 7. Perspectives (backlog *Could Have*)

- CD entièrement automatisé (déploiement sur *tag* via SSH).
- Supervision avancée (Prometheus/Grafana, Sentry).
- Application mobile native (React Native) réutilisant l'API interne.
- Codes promotionnels, détection de fraude, rapports d'administration exportables.
- Langues RTL complètes (arabe/hébreu — socle déjà en place).

---

## Références

- [DAT — Dossier d'Architecture Technique](DAT_Dossier_Architecture_Technique.md)
- [DCT — Dossier de Conception Technique](DCT_Dossier_Conception_Technique.md)
- [Comptes rendus / modifications réunion tuteur](MODIFS_REUNION_TUTEUR.md)
