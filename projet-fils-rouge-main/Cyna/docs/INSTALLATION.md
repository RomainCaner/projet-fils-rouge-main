# Guide d'installation — Cyna

## 1. Prérequis

- **PHP 8.1+** avec les extensions `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `iconv`
- **MySQL 8** (ou MariaDB 10.6+)
- Un serveur web (**Apache** avec `mod_rewrite`, ou **Nginx**) — la racine est `public/`
- Un serveur **SMTP** de test (Mailpit ou MailHog) en développement
- *(optionnel)* un compte **Stripe** en mode test pour activer les paiements réels

Aucune dépendance Composer n'est requise (projet 100 % PHP natif).

## 2. Configuration de l'environnement

Copier le fichier d'exemple puis renseigner les valeurs :

```bash
cp .env.example .env
```

Variables clés (voir `.env.example` pour la liste complète) :

| Variable | Description |
|----------|-------------|
| `APP_ENV` | `local` (débogage) ou `production` |
| `APP_URL` | URL publique sans slash final (ex. `http://localhost:8080`) |
| `APP_KEY` | Chaîne aléatoire (≥ 32 caractères) — `php -r "echo bin2hex(random_bytes(24));"` |
| `DB_*` | Connexion MySQL (hôte, port, base, utilisateur, mot de passe) |
| `MAIL_*` | Serveur SMTP (hôte `mailpit`, port `1025`, chiffrement `none` en local) |
| `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY` | Clés test ; laisser vide → paiement simulé |

## 3. Base de données

Créer la base et l'utilisateur (si nécessaire), puis importer le schéma et le jeu de
données de démonstration :

```sql
CREATE DATABASE cyna CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cyna'@'%' IDENTIFIED BY 'change_me';
GRANT ALL PRIVILEGES ON cyna.* TO 'cyna'@'%';
```

```bash
mysql -u cyna -p cyna < database/schema.sql
mysql -u cyna -p cyna < database/seed.sql      # données de démonstration (optionnel)
```

## 4. Environnement de développement

Serveur PHP intégré (racine `public/`) :

```bash
php -S localhost:8080 -t public
```

Le site est accessible sur `http://localhost:8080`, le back-office sur
`http://localhost:8080/admin`.

> **Premier accès admin** : après connexion (`admin@cyna-it.fr` / `Admin@1234`),
> la page 2FA affiche une clé secrète à saisir dans une application
> d'authentification (Google Authenticator, Authy…). Le code généré valide l'accès
> et active définitivement la 2FA.

## 5. Déploiement (Docker / production)

L'infrastructure conteneurisée (Apache/PHP, MySQL, Mailpit) est fournie par le dépôt
d'infrastructure via `docker-compose.yml`. Points d'attention en production :

1. **Racine web = `public/`** uniquement (les dossiers `src/`, `resources/`,
   `database/`, `storage/` ne doivent jamais être servis directement).
2. `APP_ENV=production` (masque les erreurs détaillées).
3. **HTTPS** obligatoire — certificat Let's Encrypt ; décommenter la redirection
   HTTPS dans `public/.htaccess` (Apache) ou configurer la redirection côté Nginx.
4. Droits d'écriture sur `storage/` et `public/uploads/` pour l'utilisateur du serveur web.
5. Sauvegardes régulières de la base MySQL.

### Exemple de configuration Nginx

```nginx
server {
    listen 80;
    server_name cyna.example.com;
    root /var/www/cyna/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* /uploads/.*\.php$ { deny all; }   # pas d'exécution PHP dans les uploads
}
```

## 6. Dépannage

| Symptôme | Cause probable |
|----------|----------------|
| Page blanche / erreur 500 | Vérifier `APP_ENV=local`, les logs PHP, la connexion MySQL |
| « jeton de sécurité invalide » (419) | Session expirée — recharger le formulaire |
| E-mails non reçus | SMTP injoignable ; vérifier `MAIL_*` et le conteneur Mailpit |
| Paiement toujours accepté | `STRIPE_SECRET_KEY` vide → mode simulé (normal en démo) |
| Images produits absentes | Visuel par défaut affiché tant qu'aucune image n'est téléversée |
