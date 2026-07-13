---
title: "Déploiement sur un VPS gratuit"
subtitle: "Site Cyna — Oracle Cloud « Always Free » · Plan d'action pas à pas"
author: "Projet fil rouge Cyna"
date: "11 juillet 2026"
---

Ce document décrit, étape par étape, le déploiement du **site web Cyna**
(PHP + MySQL, conteneurisé) sur une machine virtuelle **gratuite à vie** du
palier *Oracle Cloud Always Free*, avec **HTTPS automatique**. L'architecture
reste **identique à l'environnement de développement** (Docker), ce qui limite
les écarts entre le local et la production.

> **Rappel de périmètre.** Seul le **site web** se déploie sur un serveur.
> L'**application Java Swing** est un logiciel de bureau (`.exe`) : elle se
> *distribue*, elle ne s'héberge pas. Son unique dépendance côté serveur est
> l'accès à la base MySQL — à n'ouvrir que ponctuellement et de façon
> restreinte (voir §7).

---

## 1. Vue d'ensemble

| Critère | Choix retenu |
|---------|--------------|
| Hébergeur | Oracle Cloud Infrastructure — palier *Always Free* |
| Machine | VM Ubuntu 22.04 LTS (ARM Ampere A1 si disponible, sinon AMD E2.1.Micro) |
| Exécution | Docker + Docker Compose (image PHP 8.2/Apache + MySQL 8) |
| HTTPS | Reverse proxy **Caddy** (certificat Let's Encrypt automatique) |
| Nom de domaine | Sous-domaine gratuit **DuckDNS** (ex. `cyna-adrien.duckdns.org`) |
| Coût | 0 € (gratuit à vie sur ce palier) |
| Durée première mise en place | ~3 à 5 h (répartissables) |

**Points d'attention majeurs, à connaître avant de commencer :**

- L'inscription Oracle demande une **carte bancaire pour vérification**
  (aucun débit sur *Always Free*).
- Les VM **ARM gratuites** sont souvent affichées « Out of capacity » selon la
  région ; la **VM AMD gratuite** (1 Go de RAM) est un plan de repli fiable, à
  condition d'ajouter un **fichier d'échange (swap)**.
- Il faut ouvrir les ports sur **deux niveaux** de pare-feu (réseau Oracle
  *et* pare-feu de la VM) : c'est la cause n°1 des « ça ne répond pas ».

---

## 2. Prérequis (à préparer en amont)

1. **Compte Oracle Cloud Free Tier** — inscription sur oracle.com/cloud/free
   (carte bancaire pour vérification, non débitée).
2. **Sous-domaine DuckDNS** — création gratuite sur duckdns.org ; il servira à
   obtenir le certificat HTTPS et à donner une URL lisible au site.
3. **Clé SSH** — pour se connecter à la VM (générée lors de la création de
   l'instance ; à conserver précieusement).
4. Les **secrets de production** sous la main : clés Stripe, identifiants
   Mailjet, et un **mot de passe MySQL fort** (à définir).

---

## 3. Création de la machine virtuelle

1. Dans la console Oracle, créer une **instance de calcul** :
   - Image : **Ubuntu 22.04 LTS**.
   - Forme : **Ampere A1** (ARM, 1 OCPU / 6 Go suffisent) ; si indisponible,
     **VM.Standard.E2.1.Micro** (AMD, 1 Go).
   - Ajouter sa **clé SSH publique**.
2. Récupérer l'**adresse IP publique** de l'instance.
3. Faire pointer le sous-domaine DuckDNS sur cette IP (champ *current ip* du
   tableau de bord DuckDNS).

---

## 4. Ouverture du réseau (les deux pare-feux)

L'accès web nécessite les ports **80** (HTTP, pour le challenge Let's Encrypt)
et **443** (HTTPS).

1. **Réseau Oracle (VCN → Security List)** : ajouter deux règles d'entrée
   *Ingress* autorisant `0.0.0.0/0` sur les ports **80** et **443** (TCP).
2. **Pare-feu de la VM** : les images Oracle bloquent tout sauf le SSH. Ouvrir
   les mêmes ports au niveau du système (commandes fournies lors de la mise en
   place).

> Tant que **ces deux niveaux** ne sont pas ouverts, le site restera
> inaccessible depuis l'extérieur, même si les conteneurs tournent.

---

## 5. Installation de Docker

1. Se connecter en SSH à la VM.
2. Installer **Docker Engine** et **Docker Compose** (script d'installation
   officiel).
3. **Si la VM ne dispose que d'1 Go de RAM** (plan AMD) : créer un **fichier
   d'échange (swap) de 2 Go** pour que MySQL 8 démarre confortablement.

---

## 6. Configuration de production et déploiement

La production diffère du développement sur trois points : **HTTPS**,
**secrets** et **durcissement**. On introduit pour cela une variante Compose
dédiée.

1. **`docker-compose.prod.yml`** — trois services :
   - `web` (image PHP/Apache construite depuis le `Dockerfile` existant) ;
   - `db` (MySQL 8, avec volume persistant) ;
   - `caddy` (reverse proxy en façade, qui obtient et renouvelle **seul** le
     certificat HTTPS Let's Encrypt).
   - **phpMyAdmin n'est pas exposé** publiquement (accès base par tunnel SSH
     uniquement).
2. **Fichier d'environnement `.env` de production** (créé **sur le serveur**,
   jamais versionné) :
   - `APP_ENV=production` ;
   - `APP_URL=https://<sous-domaine>.duckdns.org` ;
   - mot de passe MySQL **fort** ;
   - clés **Stripe** et identifiants **Mailjet**.
3. **Déploiement** :
   - `git clone` du dépôt sur la VM ;
   - création du `.env` de production avec les secrets ;
   - `docker compose -f docker-compose.prod.yml up -d --build` ;
   - Caddy récupère le certificat → **le site est en ligne en HTTPS**.

---

## 7. Sécurisation et finalisation

- **Changer les mots de passe** des comptes de démonstration
  (`admin@cyna-it.fr`, `client@cyna-it.fr`) ou les retirer du jeu de données de
  production.
- Vérifier de bout en bout, **depuis le serveur** : un paiement Stripe (carte
  de test) et l'e-mail de confirmation via Mailjet.
- **Base de données** : ne pas exposer le port MySQL sur Internet. Pour
  administrer la base, passer par un **tunnel SSH**.
- **Application Java** : la conserver en usage **local**. Si une démonstration
  nécessite qu'elle atteigne la base distante, n'ouvrir le port MySQL que
  **temporairement** et **restreint à une adresse IP** précise.

---

## 8. Exploitation courante

| Opération | Commande (sur la VM) |
|-----------|----------------------|
| Mettre à jour le site | `git pull && docker compose -f docker-compose.prod.yml up -d --build` |
| Voir les journaux | `docker compose -f docker-compose.prod.yml logs -f web` |
| Sauvegarder la base | `mysqldump` planifié via `cron` (voir *Infrastructure — Sauvegardes & SSL*) |
| Redémarrer | `docker compose -f docker-compose.prod.yml restart` |

---

## 9. Alternatives envisagées

| Option | Gratuité | Stripe/Mailjet | Persistance | Verdict |
|--------|----------|----------------|-------------|---------|
| **Oracle Always Free + Docker** | À vie | Oui | 24/7 | **Retenu** |
| Cloudflare Tunnel (PC local) | Oui | Oui | Non (PC allumé) | Idéal pour une démo ponctuelle |
| Fly.io (Docker managé) | Allocation limitée | Oui | Peut s'endormir | Compromis |
| Hébergement mutualisé gratuit | Oui | **Souvent bloqués** | Oui | Déconseillé |

Les hébergements mutualisés gratuits bloquent fréquemment les **connexions
sortantes** (API Stripe, SMTP Mailjet), ce qui priverait le site de ses
fonctionnalités de paiement et d'e-mail : c'est le critère qui les écarte.
