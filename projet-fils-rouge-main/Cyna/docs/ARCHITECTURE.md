# Document de conception technique — Architecture web Cyna

## 1. Vue d'ensemble

Le pôle web suit une architecture **MVC légère** en PHP natif, organisée en couches
avec séparation stricte des responsabilités. Une **API interne cohérente** (repositories
+ services) découple les contrôleurs de l'accès aux données, ce qui prépare le partage
de la base MySQL avec l'application Java Swing.

```
Navigateur ─HTTP→ public/index.php ─→ Kernel ─→ Middlewares ─→ Contrôleur
                                                                  │
                              Services (Auth, Cart, Search, …) ───┤
                              Repositories / Models ──────────────┤
                                                                  ▼
                                                            MySQL (PDO)
Contrôleur ─→ View (gabarits PHP) ─→ Response ─→ Navigateur
```

## 2. Cycle de vie d'une requête

1. **`public/index.php`** (front controller unique) inclut `bootstrap/app.php`.
2. **Amorçage** : autoloader PSR-4 maison, chargement `.env`, `Config`, démarrage de la
   session sécurisée, données flash, internationalisation, enregistrement des routes.
3. **`Kernel::handle()`** associe la requête à une route (`Router`), exécute la chaîne de
   **middlewares** (CSRF, authentification, accès admin), puis l'action du contrôleur.
4. Le contrôleur orchestre **services** et **repositories**, puis rend un gabarit via
   **`View`** (héritage de layout + sections).
5. La **`Response`** envoie le statut, les en-têtes de sécurité et le corps.
6. Toute exception est convertie en réponse HTTP (`HttpException` → page d'erreur ;
   exception inattendue → 500, détaillée en local, masquée en production).

## 3. Couches et responsabilités

| Couche | Dossier | Rôle |
|--------|---------|------|
| Noyau | `src/Core` | Router, Request/Response, Kernel, View, Database, Session, Csrf, Validator, Paginator, Mailer, Totp, Translator, Env/Config |
| Middlewares | `src/Middleware` | Filtres transverses (CSRF, auth, admin) |
| Modèles | `src/Models` | Entité `User` (logique d'authentification) |
| Repositories | `src/Repositories` | Requêtes SQL préparées par ressource |
| Services | `src/Services` | Logique métier (Auth, Cart, SearchService, PaymentService, InvoicePdf) |
| Contrôleurs | `src/Controllers` | `Front/` (site) et `Admin/` (back-office) |
| Vues | `resources/views` | Gabarits PHP, layouts, e-mails |

## 4. Flux de données notables

- **Recherche** : `SearchController` → `SearchService` applique les facettes
  structurées en SQL (`ProductRepository::searchCandidates`), puis classe par
  pertinence textuelle (exact → 1 caractère d'écart → commence par → contient) et
  applique le tri demandé en mémoire.
- **Commande** : `CheckoutController` → `PaymentService::charge` (Stripe ou simulé) →
  `OrderRepository::createWithItems` (transaction : en-tête + lignes) →
  `SubscriptionRepository::create` pour chaque ligne (clients connectés).
- **Tableau de bord** : `DashboardController::data` expose en JSON les agrégats
  (`OrderRepository::salesByDay` / `salesByCategory`) consommés par `charts.js`
  (Canvas) — permet de changer de période sans rechargement.

## 5. Sécurité (mise en correspondance avec le plan de cadrage)

| Menace / exigence | Mesure mise en œuvre |
|-------------------|----------------------|
| Injection SQL | Requêtes préparées exclusives (`Database`), colonnes de tri en liste blanche |
| XSS | Échappement `htmlspecialchars` via le helper `e()` ; en-tête CSP |
| CSRF | Jeton par session vérifié par le middleware `VerifyCsrf` sur tout POST |
| Clickjacking | En-tête `X-Frame-Options: SAMEORIGIN` |
| Mots de passe | Hachage **bcrypt** ; politique de complexité validée côté serveur |
| Sessions | `httponly` + `samesite` + `secure` (HTTPS) + régénération après login |
| 2FA admin | **TOTP** (RFC 6238) ; enrôlement au premier accès |
| Données bancaires | Tokenisation Stripe ; seuls marque + 4 derniers chiffres conservés |
| RGPD | Modification/suppression des données depuis le compte ; pages légales |

## 6. Internationalisation & accessibilité

- **i18n** : `Translator` charge `resources/lang/{locale}.php` ; langue mémorisée en
  session ; **socle RTL** (`dir="rtl"`, CSS en propriétés logiques `margin-inline`…).
- **a11y (WCAG 2.1 AA)** : HTML sémantique, libellés `<label for>`, focus visible,
  cibles tactiles ≥ 44 px, contrastes élevés, carrousel pausable, `prefers-reduced-motion`,
  information jamais portée par la seule couleur (état « indisponible » textuel).

## 7. Performance & évolutivité

- Pagination systématique des listes (`Paginator`, taille paramétrable au back-office).
- Index SQL ciblés (tri catalogue, jetons d'auth, recherche `FULLTEXT`).
- Images en `.webp`, chargement différé (`loading="lazy"`).
- Architecture modulaire : ajouter une ressource = un repository + un contrôleur + des
  routes, sans impacter l'existant (principe de responsabilité unique, esprit SOLID).

## 8. Conventions de code

- PHP `declare(strict_types=1)`, typage des signatures, `final` par défaut.
- Nommage : `camelCase` (PHP), `snake_case` (colonnes SQL), `kebab-case` (URL/CSS).
- Montants stockés en **centimes** (entiers) pour éviter les imprécisions monétaires.
- Une branche Git par fonctionnalité, fusion après revue (cf. note de cadrage).
- **Base de données en français** (tables et colonnes : `produits`, `utilisateurs`,
  `total_centimes`…). Les **repositories** assurent la correspondance vers les clés du
  domaine applicatif via des **alias SQL** (ex: `SELECT nom AS name`), de sorte que les
  contrôleurs et les vues restent indépendants de la nomenclature physique de la base.
  Les valeurs d'énumération (statuts, périodicités) restent en anglais car elles servent
  d'identifiants techniques et de clés d'internationalisation.
