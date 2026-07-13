# Cyna — Plateforme e-commerce SaaS

Site web e-commerce **mobile-first** permettant la vente en ligne des solutions de
cybersécurité SaaS de Cyna (**SOC, EDR, XDR**), accompagné d'un **back-office**
d'administration. Ce dépôt correspond au pôle applicatif web du projet fil rouge
(l'application Java Swing de gestion interne et l'infrastructure Docker font l'objet
de dépôts/livrables distincts).

## Stack technique

| Composant | Choix |
|-----------|-------|
| Backend | **PHP 8.2 natif**, sans framework ni dépendance Composer |
| Frontend | HTML5, CSS3, JavaScript natif (design system maison) |
| Base de données | **MySQL 8** (PDO, requêtes préparées) |
| Paiement | **Stripe** (mode test, via API REST en cURL) — repli simulé sans clés |
| E-mails | Client **SMTP** maison (compatible Mailpit/MailHog) |
| 2FA | **TOTP** (RFC 6238) implémenté à la main |
| Conteneurisation | Docker / Docker Compose *(dépôt infrastructure)* |

> Conformément à la note de cadrage, **aucune dépendance externe** n'est utilisée :
> autoloader PSR-4, mailer SMTP, générateur TOTP, générateur de PDF de facture et
> client Stripe sont tous écrits à la main.

## Fonctionnalités principales

**Front-office (client)**
- Page d'accueil éditable (carrousel, texte, grille de catégories, top produits)
- Catalogue par catégorie avec tri métier (disponibilité + priorité) et pagination
- Fiche produit (illustrations, caractéristiques, abonnement mensuel/annuel, similaires)
- Recherche avancée à facettes (texte, catégories, prix, disponibilité) + tri
- Panier (connecté ou invité), tunnel de commande, paiement Stripe, confirmation
- Authentification : inscription + confirmation e-mail, connexion (« se souvenir de moi »),
  mot de passe oublié
- Espace compte : profil, carnet d'adresses, moyens de paiement, abonnements,
  historique des commandes groupé par année + **facture PDF téléchargeable**
- Formulaire de contact, pages légales, **i18n FR/EN** (+ socle RTL), accessibilité WCAG 2.1 AA

**Back-office (administrateur)**
- Connexion sécurisée **mot de passe + 2FA TOTP** (enrôlement au premier accès)
- Tableau de bord (histogramme des ventes, camembert par catégorie, KPI)
- CRUD produits (liste triable, recherche, **suppression multiple**), catégories
- Gestion des commandes (statuts), consultation des utilisateurs
- Personnalisation de la page d'accueil, boîte de réception des messages

## Comptes de démonstration (après import du seed)

| Rôle | E-mail | Mot de passe |
|------|--------|--------------|
| Administrateur | `admin@cyna-it.fr` | `Admin@1234` *(2FA à configurer au 1er accès)* |
| Client | `client@cyna-it.fr` | `Client@1234` |

## Démarrage rapide

```bash
cp .env.example .env          # puis renseigner DB, SMTP, Stripe
# Importer la base (voir docs/INSTALLATION.md) :
#   mysql -u cyna -p cyna < database/schema.sql
#   mysql -u cyna -p cyna < database/seed.sql
```

La racine web est le dossier **`public/`**. En développement :

```bash
php -S localhost:8080 -t public
```

Détails complets : **[docs/INSTALLATION.md](docs/INSTALLATION.md)**.
Architecture et choix techniques : **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)**.

## Structure du projet

```
Cyna/
├── public/              # Racine web : index.php (front controller), .htaccess, assets
├── bootstrap/app.php    # Amorçage : autoloader, env, session, i18n, routes
├── routes/              # web.php (front) et admin.php (back-office)
├── src/
│   ├── Core/            # Noyau : Router, Request/Response, View, Database, sécurité…
│   ├── Middleware/      # CSRF, authentification, accès admin
│   ├── Models/          # Entité User
│   ├── Repositories/    # Accès données (produits, commandes, abonnements…)
│   ├── Services/        # Auth, Cart, Search, PaymentService, InvoicePdf
│   ├── Controllers/     # Front/ et Admin/
│   └── Support/         # Helpers globaux, upload d'images
├── resources/
│   ├── views/           # Gabarits PHP (layouts, front, admin, e-mails)
│   └── lang/            # Traductions fr.php / en.php
├── database/            # schema.sql + seed.sql
└── storage/             # logs, factures, cache (générés à l'exécution)
```

## Sécurité

- Requêtes **préparées** (anti-injection SQL) ; échappement HTML systématique (anti-XSS)
- **Jeton CSRF** sur tous les formulaires ; en-têtes CSP, X-Frame-Options, nosniff
- Mots de passe **bcrypt** ; sessions httponly/secure + régénération après connexion
- **2FA TOTP** obligatoire pour le back-office
- Données de carte **jamais stockées en clair** (tokenisation Stripe) ; conformité RGPD
