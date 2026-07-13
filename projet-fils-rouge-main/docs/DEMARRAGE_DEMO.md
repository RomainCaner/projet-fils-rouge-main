# Guide de démarrage & préparation de la réunion tuteur

Ce guide explique **comment lancer l'application sur un autre ordinateur** (qui
possède déjà Docker) et propose une **série de questions à poser au tuteur**.

---

## Partie 1 — Lancer l'application, étape par étape

### Prérequis (à vérifier avant la réunion)

- **Docker Desktop** installé **et lancé** (l'icône baleine doit être active).
- **Git** installé (pour récupérer le code).
- Une connexion internet (pour cloner le dépôt et télécharger les images Docker).

> Astuce : lance Docker Desktop **10 minutes avant** la réunion, le temps qu'il
> démarre complètement.

### Étape 1 — Récupérer le code depuis GitHub

Ouvre un terminal (PowerShell sous Windows) et tape :

```powershell
git clone https://github.com/Inedra15/projet-fils-rouge.git
cd projet-fils-rouge/Cyna
```

### Étape 2 — Démarrer l'application

```powershell
docker compose down -v
docker compose up -d --build
```

> **Pourquoi `docker compose down -v` d'abord ?** La base de données n'est remplie
> avec les données de démonstration **qu'à sa toute première création**. Si une
> ancienne base traîne déjà sur la machine (d'un essai précédent), Docker la
> réutilise **sans recharger les données** — ce qui provoque des **images
> cassées** (chemins qui ne correspondent plus). Le `-v` supprime cette ancienne
> base pour repartir **totalement propre**. Sur une machine où le projet n'a
> jamais tourné, cette commande ne fait rien de gênant : on la met **par
> sécurité**.

> La **première fois**, `up` télécharge et construit les images : cela peut
> prendre **quelques minutes**. La base est ensuite **créée et remplie
> automatiquement** (catalogue, comptes de démo, codes de réduction).

### Étape 3 — Vérifier que tout tourne

```powershell
docker compose ps
```

Tu dois voir les services `web`, `db`, `mailpit`, `phpmyadmin` avec l'état
« running / healthy ».

### Étape 4 — Ouvrir les interfaces dans le navigateur

| Interface | Adresse | À quoi ça sert |
|-----------|---------|----------------|
| **Site e-commerce** | http://localhost:8080 | La démo principale |
| **Back-office admin** | http://localhost:8080/admin/connexion | Gestion (produits, commandes, promotions…) |
| **phpMyAdmin** | http://localhost:8081 | Visualiser la base de données |
| **Mailpit** | http://localhost:8025 | **Boîte e-mail de test** : voir les e-mails de l'app |

> 📧 **Important — les e-mails ne partent pas vers de vraies boîtes.** En local,
> ils sont **capturés par Mailpit**. Pour voir l'e-mail de confirmation
> d'inscription (et cliquer sur le lien de validation), ouvre **http://localhost:8025**.
> C'est volontaire : on n'envoie jamais de vrais e-mails depuis un environnement
> de test, et **aucun mot de passe SMTP n'est stocké dans le dépôt** (bonne
> pratique de sécurité).

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|------|--------|--------------|
| Administrateur | `admin@cyna-it.fr` | `Admin@1234` *(2FA à configurer au 1er accès : scanner le QR code avec Google Authenticator)* |
| Client | `client@cyna-it.fr` | `Client@1234` |

### Étape 5 — Que montrer au tuteur (parcours conseillé)

1. **Accueil** → carrousel, catégories, top produits.
2. **Catalogue + recherche** → filtres (prix, catégorie), tri.
3. **Fiche produit** → abonnement mensuel/annuel.
4. **Panier + code promo** → ajouter un service, saisir `CYNA25` ou `BIENVENUE10`
   → la remise s'applique (sous-total → remise → total).
5. **Checkout** → paiement (en **mode simulé** : pas de vrai débit) → confirmation
   + facture PDF téléchargeable.
6. **Inscription** → créer un compte, puis ouvrir **http://localhost:8025**
   (Mailpit) pour montrer l'e-mail de confirmation et cliquer sur le lien.
7. **Espace compte** → commandes, abonnements, adresses.
8. **Back-office** → tableau de bord (graphiques), **Promotions** (créer un code),
   **Commandes** (+ bouton **Exporter CSV**), **Messages** de contact.

### Étape 6 (bonus qui impressionne) — lancer les tests devant le tuteur

```powershell
# Tests du site (depuis le dossier Cyna) :
docker run --rm -v "${PWD}:/app" -w /app composer:2 sh -c "composer install && vendor/bin/phpunit --testdox"

# Tests de l'application Java (depuis le dossier projet-fils-rouge) :
cd ..
docker run --rm -v "${PWD}:/app" -v cyna-m2:/root/.m2 -w /app maven:3.9-eclipse-temurin-17 mvn test
```

> ⚠️ **Invite de commandes (cmd)** au lieu de PowerShell ? Remplace `${PWD}` par
> `%cd%`.

### Étape 7 — Arrêter à la fin

```powershell
docker compose down
```

---

## Partie 2 — L'application Java (back-office lourd) *(optionnel)*

L'application Java est **secondaire** pour la démo ; le plus important est le site
web. Deux options pour la montrer :

- **Option simple (recommandée)** : apporter sur une **clé USB** le dossier déjà
  compilé `CynaAdminPanel-livrable` et double-cliquer sur `CynaAdminPanel.exe`.
  Il fonctionne **sans rien installer** (Java est embarqué), à condition que la
  **base Docker soit démarrée** (l'app se connecte à MySQL sur le port 3307).

- **Option build** : la construire avec Maven (via Docker), puis la lancer avec
  Java 17 installé sur la machine :

  ```powershell
  # Depuis projet-fils-rouge/ :
  docker run --rm -v "${PWD}:/app" -v cyna-m2:/root/.m2 -w /app maven:3.9-eclipse-temurin-17 mvn package
  java -jar target/CynaAdminPanel.jar
  ```

> Détail sans impact pour la démo : le chemin des images
> (`app.images.dir` dans `application.properties`) pointe vers l'ancien PC ; cela
> ne gêne que l'upload d'images depuis l'app Java, pas le reste.

---

## Partie 3 — Questions à poser au tuteur

Pour savoir si vous êtes « dans le bon », voici des questions regroupées par thème.

### Périmètre & conformité

1. Notre périmètre **Must Have** (site responsive, abonnements, tunnel de commande
   Stripe, espace compte, authentification, back-office avec 2FA, application Java,
   sécurité) est-il jugé **suffisant** pour valider le bloc ?
2. Le choix d'un **site rendu côté serveur (PHP)** plutôt qu'une **SPA** est-il
   **accepté**, ou considéré comme un point de non-conformité ?
3. Avons-nous eu raison de classer l'**application mobile native** et le **chatbot**
   en « Won't Have » (remplacés par le site responsive) ?

### Technique

4. **Le jour du rendu, les examinateurs lanceront-ils l'application en local**
   (les e-mails sont alors visibles dans Mailpit), ou souhaitent-ils **recevoir
   les e-mails dans leur propre boîte** ? *(Ce second cas impliquerait un
   déploiement sur serveur avec un vrai service d'envoi d'e-mails.)*
5. Le **paiement Stripe en mode simulé** suffit-il pour la soutenance, ou faut-il
   impérativement un **paiement réel avec des clés de test** ?
6. Le **niveau de tests** actuel (77 tests PHP + 10 Java) est-il suffisant, ou
   attendez-vous une couverture plus large / d'autres types de tests (intégration,
   end-to-end) ?
7. Notre **architecture** (PHP natif sans framework, deux applications partageant
   une même base MySQL, déploiement Docker) correspond-elle à vos attentes ?

### Livrables & documentation

8. La **documentation** fournie (DCT, cahier de recettes, manuel utilisateur, doc
   API, guides) correspond-elle à ce qui est attendu, en **contenu** et en **format** ?
9. L'exigence des **deux dépôts Git distincts** (site web / application Java) est-elle
   stricte, et peut-on la satisfaire **juste avant le rendu** ?
10. Le DCT doit-il inclure des **diagrammes UML** supplémentaires (cas d'utilisation,
    séquence, classes) au-delà de ceux déjà présents ?

### Organisation & évaluation

11. Comment se répartit l'**évaluation individuelle vs groupe**, et sur quels
    **critères précis** serons-nous notés ?
12. Y a-t-il des **jalons intermédiaires** à respecter d'ici le rendu de juillet ?
13. Qu'attendez-vous précisément pour la **soutenance** (durée, démo en direct,
    support de présentation) ?

### Points ouverts / priorisation

14. Faut-il implémenter la **détection de fraude** (Could Have), ou est-ce hors
    attentes pour ce niveau ?
15. Sur **quoi devrions-nous concentrer nos efforts en priorité** dans les semaines
    restantes ?

---

## Dépannage rapide

| Problème | Cause | Solution |
|----------|-------|----------|
| Les **images ne s'affichent pas** | Une ancienne base traînait sur la machine (non rechargée) | `docker compose down -v` puis `docker compose up -d --build`, puis **Ctrl+F5** dans le navigateur |
| **Pas d'e-mail** reçu à l'inscription | Normal : les e-mails ne partent pas vers de vraies boîtes en local | Les consulter sur **http://localhost:8025** (Mailpit) |
| Un **service ne démarre pas** | Docker Desktop pas prêt, ou port déjà utilisé | Vérifier que Docker est lancé ; `docker compose ps` pour l'état ; relancer `up` |
| **Modifs pas prises en compte** | Version locale non à jour | `git pull` puis relancer `docker compose up -d --build` |

---

## Aide-mémoire express (à garder sous les yeux)

```
1. Lancer Docker Desktop (attendre qu'il soit prêt)
2. git clone https://github.com/Inedra15/projet-fils-rouge.git
   (ou "git pull" si déjà cloné)
3. cd projet-fils-rouge/Cyna
4. docker compose down -v       <-- repart d'une base propre (images OK)
5. docker compose up -d --build
6. Ouvrir http://localhost:8080   (Ctrl+F5 si besoin)
7. E-mails de test : http://localhost:8025  (Mailpit)
8. Admin : admin@cyna-it.fr / Admin@1234
9. À la fin : docker compose down
```
