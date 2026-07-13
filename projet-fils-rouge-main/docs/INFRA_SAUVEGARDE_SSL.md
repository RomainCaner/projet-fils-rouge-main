# Infrastructure — Sauvegardes & Certificats SSL

Ce document décrit les procédures d'exploitation liées à la **sauvegarde de la
base de données** et à la **sécurisation des communications (HTTPS)**, exigées
par la note de cadrage (§4.4, §5.3).

---

## 1. Sauvegarde automatisée de la base MySQL

### 1.1 Principe

Une tâche planifiée (`cron`) exécute un `mysqldump` quotidien, compresse
l'archive et applique une **rétention glissante** (par défaut 14 jours). Le
script est fourni dans [`scripts/backup-db.sh`](scripts/backup-db.sh).

### 1.2 Planification (cron)

```cron
# Sauvegarde quotidienne à 02h30
30 2 * * * /opt/cyna/scripts/backup-db.sh >> /var/log/cyna-backup.log 2>&1
```

### 1.3 Restauration

```bash
# Décompresser puis réinjecter dans la base
gunzip -c /opt/cyna/backups/cyna-2026-07-01.sql.gz | \
  docker compose exec -T db mysql -u cyna -p"$DB_PASSWORD" cyna
```

> **Bonne pratique :** tester régulièrement une restauration sur un environnement
> de pré-production (un backup jamais restauré n'est pas un backup).

### 1.4 Recommandations de résilience

- Copier les archives vers un **stockage externe** (objet S3, autre VPS) pour se
  prémunir d'une perte totale du serveur.
- Chiffrer les archives contenant des données personnelles (RGPD).

---

## 2. Certificat SSL / HTTPS (Let's Encrypt)

### 2.1 Émission

Avec un reverse proxy Nginx et `certbot` :

```bash
sudo certbot --nginx -d cyna.example.com -d www.cyna.example.com
```

### 2.2 Renouvellement automatique

Certbot installe une tâche de renouvellement. Vérification :

```bash
sudo certbot renew --dry-run
```

### 2.3 Redirection HTTP → HTTPS (extrait Nginx)

```nginx
server {
    listen 80;
    server_name cyna.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cyna.example.com;

    ssl_certificate     /etc/letsencrypt/live/cyna.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cyna.example.com/privkey.pem;

    # En-têtes de sécurité (complètent ceux émis par l'application)
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        proxy_pass http://web:80;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-For $remote_addr;
    }
}
```

> L'application détecte `X-Forwarded-Proto: https` pour activer le drapeau
> `secure` des cookies de session.
